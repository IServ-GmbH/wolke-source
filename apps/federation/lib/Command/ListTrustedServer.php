<?php

namespace OCA\Federation\Command;

use OC\Core\Command\Base;
use OCA\Federation\TrustedServers;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListTrustedServer extends Base
{
    protected string $defaultOutputFormat = self::OUTPUT_FORMAT_JSON;
    private TrustedServers $trustedServers;

    public function __construct(TrustedServers $trustedServers)
    {
        parent::__construct();

        $this->trustedServers = $trustedServers;
    }

    protected function configure()
    {
        $this
            ->setName('federation:list-trusted-server')
            ->setDescription('List all trusted servers for federation sharing.')
            ->addOption(
                'output',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output format (json or json_pretty, json is default)',
                $this->defaultOutputFormat
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $serverList = $this->formatGetServersResult($this->trustedServers->getServers());
        $this->writeArrayInOutputFormat($input, $output, $serverList);

        return 0;
    }

    private function formatGetServersResult(array $serverList): array
    {
        /**
         * A server is considered healthy if we were not denied by it and the secret share was successful
         * If the shared secret is null, it means the shared secret handshake did not succeed yet.
         * We assume that the handshake takes up to a day for a server to be considered healthy.
         * Status 3 (error occurred) may just be a network hiccup, so we consider it healthy as well.
         * Status 2 with a shared secret means, the handshake was successful, but the address-book-sync did not happen yet.
         * Status 4 is unhealthy, because the server intentionally denied us. The shared secret is probably wrong, we need a new handshake.
         *
         * We remove unhealthy servers and readd them again. This is the only mechanism to restart the shared secret handshake.
         */
        $adjustedServer = [];
        foreach ($serverList as $server) {
            $adjustedServer[] = [
                'url' => $server['url'],
                'healthy' => $server['status'] !== TrustedServers::STATUS_ACCESS_REVOKED,
            ];
        }

        return  $adjustedServer;
    }
}
