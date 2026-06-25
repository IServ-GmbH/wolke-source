<?php

namespace OCA\Federation\Command;

use OC\Core\Command\Base;
use OCA\Federation\BackgroundJob\GetSharedSecret;
use OCA\Federation\BackgroundJob\RequestSharedSecret;
use OCA\Federation\TrustedServers;
use OCP\BackgroundJob\IJobList;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListTrustedServer extends Base
{
    protected string $defaultOutputFormat = self::OUTPUT_FORMAT_JSON;
    private TrustedServers $trustedServers;
    private IJobList $jobList;

    public function __construct(TrustedServers $trustedServers, IJobList $jobList)
    {
        parent::__construct();

        $this->trustedServers = $trustedServers;
        $this->jobList = $jobList;
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
         * Healthiness decides whether the IServ member sync removes and re-adds a server,
         * which is the only way to restart the shared-secret handshake. We therefore key it
         * on whether a usable secret exists, not on the raw status:
         *
         * - A server that has a shared secret is healthy. Status 2 (pending) with a secret is
         *   merely waiting for the first (daily) address-book sync, and status 3 (error) with
         *   a secret is an established server that hit a transient sync error and recovers on
         *   its own. Re-adding either would throw away a working secret.
         * - A server without a secret is healthy only while a handshake job (RequestSharedSecret
         *   or GetSharedSecret) is still trying to obtain one. If neither a secret nor a job
         *   exist, the handshake can never complete, so the server is unhealthy and must be
         *   re-added.
         * - Status 4 (access revoked) is always unhealthy: the remote denied us and any stored
         *   secret is stale, so we need a fresh handshake.
         *
         * ASSUMPTION (member-sync cadence): this is only safe if the IServ member sync runs far
         * less often than a handshake takes to complete. There is a window in which a perfectly
         * healthy handshake has neither a secret nor a job: once our RequestSharedSecret POST is
         * accepted (HTTP 200) Nextcloud drops the job and waits for the remote to push the secret
         * back on its own next cron run. A server caught in that window is reported unhealthy
         * here, so a sync landing in it re-adds the server, which mints a new token and aborts the
         * in-flight handshake. A single stray re-add is self-healing, but syncs arriving faster
         * than the handshake completes (at least one remote cron cycle, longer while peers are
         * slow) reset it every time and it never converges. trusted_servers has no creation
         * timestamp to grace-period against, so the whole safety margin lives in the sync cadence.
         */
        $pendingHandshakeUrls = $this->urlsWithPendingHandshakeJob();

        $adjustedServer = [];
        foreach ($serverList as $server) {
            $adjustedServer[] = [
                'url' => $server['url'],
                // The raw status is exposed so consumers (e.g. the IServ federation metrics
                // endpoint) can report per-status counts. STATUS_OK = 1, STATUS_PENDING = 2,
                // STATUS_FAILURE = 3, STATUS_ACCESS_REVOKED = 4.
                'status' => (int)$server['status'],
                'healthy' => $this->isServerHealthy($server, $pendingHandshakeUrls),
            ];
        }

        return $adjustedServer;
    }

    /**
     * Returns the set of server URLs that still have a pending shared-secret handshake job.
     *
     * Both directions count: RequestSharedSecret (we ask the remote to exchange a secret) and
     * GetSharedSecret (we fetch the secret the remote prepared for us). Depending on which side
     * won the token tie-break in OCSAuthAPIController::requestSharedSecret(), the live job may be
     * either one, so checking only RequestSharedSecret would miss in-flight handshakes.
     *
     * @return array<string, true>
     */
    private function urlsWithPendingHandshakeJob(): array
    {
        $urls = [];
        foreach ([RequestSharedSecret::class, GetSharedSecret::class] as $jobClass) {
            foreach ($this->jobList->getJobsIterator($jobClass, null, 0) as $job) {
                $urls[$job->getArgument()['url']] = true;
            }
        }

        return $urls;
    }

    /**
     * @param array<string, true> $pendingHandshakeUrls
     */
    private function isServerHealthy(array $server, array $pendingHandshakeUrls): bool
    {
        if ((int)$server['status'] === TrustedServers::STATUS_ACCESS_REVOKED) {
            return false;
        }

        if (!empty($server['shared_secret'])) {
            return true;
        }

        return isset($pendingHandshakeUrls[$server['url']]);
    }
}
