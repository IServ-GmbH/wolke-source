<?php

namespace OCA\Federation\Command;

use OCA\Federation\TrustedServers;
use OCP\DB\Exception as DBException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveTrustedServer extends Command
{
    private TrustedServers $trustedServers;

    public function __construct(TrustedServers $trustedServers)
    {
        parent::__construct();

        $this->trustedServers = $trustedServers;
    }

    protected function configure()
    {
        $this
            ->setName('federation:remove-trusted-server')
            ->setDescription('Remove trusted server(s) from federation sharing. Any number of URLs, separated by spaces, can be passed to the command.')
            ->addArgument(
                'server',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $serverUrlsToRemove = $input->getArgument('server');
        $trustedServers = $this->trustedServers->getServers();

        foreach ($trustedServers as $trustedServer) {
            if (in_array($trustedServer['url'], $serverUrlsToRemove)) {
                try {
                    $this->trustedServers->removeServer($trustedServer['id']);
                    $output->writeln(
                        sprintf('<info>"Server %s successfully removed"</info>', $trustedServer['url'])
                    );
                } catch (DBException $exception) {
                    $output->writeln(
                        sprintf('<error>"Could not remove server %s"</error>', $exception->getMessage())
                    );
                }
            }
        }

        return 0;
    }
}
