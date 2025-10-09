<?php

namespace OCA\Federation\Command;

use OCA\Federation\BruteforceResetter;
use OCA\Federation\TrustedServers;
use OCP\HintException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddTrustedServer extends Command
{
    public function __construct(
        private readonly TrustedServers $trustedServers,
        private readonly BruteforceResetter $bruteforceResetter,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('federation:add-trusted-server')
            ->setDescription('Add trusted server(s) for federation sharing. Any number of URLs, separated by spaces, can be passed to the command.')
            ->addArgument(
                'server',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $serverUrls = $input->getArgument('server');

        foreach ($serverUrls as $url) {
            try {
                if ($this->checkServer($url)) {
                    $this->bruteforceResetter->resetTrustedServerAttempts($url);
                    $this->trustedServers->addServer($url);
                    $output->writeln(
                        sprintf('<info>"Server %s successfully added"</info>', $url)
                    );
                }
            } catch (HintException $exception) {
                $output->writeln(
                    sprintf('<error>"%s"</error>', $exception->getMessage())
                );
            }
        }

        return 0;
    }

    /**
     * @throws HintException
     */
    private function checkServer(string $url): bool
    {
        if ($this->trustedServers->isTrustedServer($url) === true) {
            throw new HintException(sprintf('Could not add "%s". Server is already in the list of trusted servers.', $url));
        }

        if ($this->trustedServers->isNextcloudServer($url) === false) {
            throw new HintException(sprintf('Could not add "%s". No server to federate with found.', $url));
        }

        return true;
    }
}
