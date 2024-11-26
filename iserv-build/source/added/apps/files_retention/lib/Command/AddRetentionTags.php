<?php

declare(strict_types=1);

namespace OCA\Files_Retention\Command;

use OC\SystemTag\SystemTagManager;
use OCA\Files_Retention\BackgroundJob\RetentionJob;
use OCP\BackgroundJob\IJobList;
use OCP\IDBConnection;
use OCP\SystemTag\TagNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AddRetentionTags extends Command
{
    public const TIMEUNITS = ['D' => 0, 'W' => 1, 'M' => 2, 'Y' => 3];

    public function __construct(
        private SystemTagManager $tagManager,
        private IDBConnection $db,
        private IJobList $jobList,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('files_retention:add-retention-tags')
            ->setDescription('Adds retention tags to the system based on JSON input from stdin')
            ->setHelp(
                "This command expects a JSON input as an input stream with an array of objects that contain the following fields:\n\n" .
                "  name: A string representing the name associated with the command (should be a combination of time amount and unit, e.g., '20 days').\n" .
                "  timeunit: The unit of time (e.g., 'D' (days) ', 'W' (weeks), 'M' (month), 'Y' (years)) to specify the duration.\n" .
                "  timeamount: An integer representing the quantity of the time unit.\n\n" .
                "Example input:\n\n" .
                "[{\n" .
                "  \"name\": \"15 days\",\n" .
                "  \"timeunit\": \"D\",\n" .
                "  \"timeamount\": 15\n" .
                "}]\n\n" .
                "Ensure the JSON is correctly formatted and passed as an input stream for the command to execute successfully."
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Adding Retention Tags');

        $jsonData = stream_get_contents(STDIN);
        $requiredTags = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $io->error('Invalid JSON input');
            return Command::FAILURE;
        }

        $qb = $this->db->getQueryBuilder();

        foreach ($requiredTags as $requiredTag) {
            try {
                $currentTag = $this->tagManager->getTag($requiredTag['name'], true, true);

                $qb->select('*')
                    ->from('retention')
                    ->where('tag_id = :id')
                    ->setParameter('id', $currentTag->getId());

                $retention = $qb->execute()->fetch();

                if (!$retention) {
                    $tagId = $this->createRetentionTag($currentTag->getId(), $requiredTag, $io);
                    $this->jobList->add(RetentionJob::class, ['tag' => $tagId]);
                    $io->success("Retention tag added for '{$requiredTag['name']}'");
                } else {
                    $io->warning("Retention tag already exists for '{$requiredTag['name']}'");
                }

            } catch (TagNotFoundException $exception) {
                $io->warning("Tag '{$requiredTag['name']}' not found. Creating new tag.");

                $tag = $this->tagManager->createTag($requiredTag['name'], true, true);
                $tagId = $this->createRetentionTag($tag->getId(), $requiredTag, $io);

                $this->jobList->add(RetentionJob::class, ['tag' => $tagId]);
                $io->success("Created and added retention tag for '{$requiredTag['name']}'");
            } catch (\Exception $e) {
                $io->error("Error processing tag '{$requiredTag['name']}': " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $io->success('Retention tags processing completed');
        return Command::SUCCESS;
    }

    private function createRetentionTag(string $tagId, array $requiredTag, SymfonyStyle $io): int
    {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->insert('retention')
                ->setValue('tag_id', $qb->createNamedParameter($tagId))
                ->setValue('time_unit', $qb->createNamedParameter(self::TIMEUNITS[$requiredTag['timeunit']]))
                ->setValue('time_amount', $qb->createNamedParameter($requiredTag['timeamount']))
                ->setValue('time_after', $qb->createNamedParameter(0));

            $qb->executeStatement();

            return $qb->getLastInsertId();

        } catch (\Exception $e) {
            $io->error("Failed to create retention tag for tag ID $tagId: " . $e->getMessage());
            throw $e;
        }
    }
}
