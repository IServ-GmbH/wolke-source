<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Support\Repair;

use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Support\Subscription\IRegistry;

class SwitchUpdaterServer implements IRepairStep {
	public function __construct(
		protected readonly IConfig $config,
		protected readonly IRegistry $subscriptionRegistry,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Switches from default updater server to the customer one if a valid subscription is available';
	}

	#[\Override]
	public function run(IOutput $output): void {
		if ($this->config->getAppValue('support', 'SwitchUpdaterServerHasRun') === 'yes') {
			$output->info('Repair step already executed');
			return;
		}

		$currentUpdaterServer = $this->config->getSystemValue('updater.server.url', 'https://updates.nextcloud.com/updater_server/');
		$subscriptionKey = $this->config->getAppValue('support', 'subscription_key', '');

		/**
		 * only overwrite the updater server if:
		 * 	- it is the default one
		 *  - there is a valid subscription
		 *  - there is a subscription key set
		 *  - the subscription key is halfway sane
		 */
		if ($currentUpdaterServer === 'https://updates.nextcloud.com/updater_server/' &&
			$this->subscriptionRegistry->delegateHasValidSubscription() &&
			$subscriptionKey !== '' &&
			preg_match('!^[a-zA-Z0-9-]{10,250}$!', $subscriptionKey)
		) {
			$this->config->setSystemValue('updater.server.url', 'https://updates.nextcloud.com/customers/' . $subscriptionKey . '/');
		}

		// if everything is done, no need to redo the repair during next upgrade
		$this->config->setAppValue('support', 'SwitchUpdaterServerHasRun', 'yes');
	}
}
