<?php

declare(strict_types=1);

namespace OCA\Files_Retention\Command;

use OC\SystemTag\SystemTagManager;
use OCP\IDBConnection;
use OCP\SystemTag\TagNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckRetentionTags extends Command
{
    public function __construct(
        private SystemTagManager $tagManager,
        private IDBConnection $db,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('files_retention:check-retention-tags')
            ->setDescription('Checks if specified retention tags exist in the system, based on JSON input from stdin')
            ->setHelp(
                "This command expects a JSON input as an input stream with the following fields:\n\n" .
                "  name: A string representing the name associated with the command (should be a combination of time amount and unit, e.g., '20 days').\n" .
                "  timeunit: The unit of time (e.g., 'D' (days) ', 'W' (weeks), 'M' (month), 'Y' (years)) to specify the duration.\n" .
                "  timeamount: An integer representing the quantity of the time unit.\n\n" .
                "Example input:\n\n" .
                "{\n" .
                "  \"name\": \"15 days\",\n" .
                "  \"timeunit\": \"D\",\n" .
                "  \"timeamount\": 15\n" .
                "}\n\n" .
                "Ensure the JSON is correctly formatted and passed as an input stream for the command to execute successfully."
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Checking Retention Tags');

        $jsonData = stream_get_contents(STDIN);
        $requiredTags = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $io->error('Invalid JSON input');
            return Command::FAILURE;
        }

        $missingTags = [];
        $allTagsExist = true;

        $qb = $this->db->getQueryBuilder();

        foreach ($requiredTags as $requiredTag) {
            try {
                $currentTag = $this->tagManager->getTag($requiredTag['name'], true, true);

                $qb->select('*')
                    ->from('retention', 'r')
                    ->where('r.tag_id = :tag')
                    ->setParameter('tag', $currentTag->getId());

                $retentionTag = $qb->executeQuery()->fetch();

                if (!$retentionTag) {
                    $io->warning("Retention tag for '{$requiredTag['name']}' not found.");
                    $missingTags[] = $requiredTag['name'];
                    $allTagsExist = false;
                } else {
                    $io->success("Retention tag exists for '{$requiredTag['name']}'.");
                }

            } catch (TagNotFoundException $exception) {
                $io->error("Tag '{$requiredTag['name']}' not found.");
                $missingTags[] = $requiredTag['name'];
                $allTagsExist = false;
            } catch (\Exception $e) {
                $io->error("An error occurred while checking tag '{$requiredTag['name']}': " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        if ($allTagsExist) {
            $io->success('All retention tags are correctly set up.');
            return Command::SUCCESS;
        } else {
            $io->warning('Some retention tags are missing or not set up correctly.');
            $io->listing($missingTags);
            return Command::FAILURE;
        }
    }
}
