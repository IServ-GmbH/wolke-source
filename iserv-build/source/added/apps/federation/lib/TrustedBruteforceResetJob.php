<?php

declare(strict_types=1);

namespace OCA\Federation;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class TrustedBruteforceResetJob extends TimedJob
{
	public function __construct(
		ITimeFactory $timeFactory,
		private readonly TrustedServers $trustedServers,
		private readonly BruteforceResetter $bruteforceResetter,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct($timeFactory);
		$this->setInterval(24 * 60 * 60); //run once a day
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	protected function run($argument)
	{
		$servers = $this->trustedServers->getServers();
		$this->logger->info('Starting trusted servers bruteforce reset job for ' . count($servers) . ' servers.');
		foreach ($servers as $server) {
			$this->bruteforceResetter->resetTrustedServerAttempts($server['url']);
		}
		$this->logger->info('Reset brute-force attempts for ' . count($servers) . ' trusted servers.');
	}
}
