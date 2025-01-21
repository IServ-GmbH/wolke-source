<?php

namespace OCA\Federation\Command;

use OC\Core\Command\Base;
use OCA\Federation\TrustedServers;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListTrustedServer extends Base
{
    private const HEALTHY_SERVER_STATUSES = [
        TrustedServers::STATUS_OK,
        TrustedServers::STATUS_PENDING,
    ];

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
        $adjustedServer = [];
        foreach ($serverList as $server) {
            $adjustedServer[] = [
                'url' => $server['url'],
                'healthy' => in_array($server['status'], self::HEALTHY_SERVER_STATUSES, true),
            ];
        }

        return  $adjustedServer;
    }
}
