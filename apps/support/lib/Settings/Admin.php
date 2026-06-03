<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Support\Settings;

use OCA\Support\Service\SubscriptionService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\ServerVersion;
use OCP\Settings\IDelegatedSettings;

class Admin implements IDelegatedSettings {

	public function __construct(
		protected readonly IConfig $config,
		protected readonly IAppConfig $appConfig,
		protected readonly IUserManager $userManager,
		protected readonly IURLGenerator $urlGenerator,
		protected readonly SubscriptionService $subscriptionService,
		protected readonly ServerVersion $serverVersion,
	) {
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		$userCount = $this->subscriptionService->getUserCount();
		$activeUserCount = $this->userManager->countSeenUsers();

		$instanceSize = 'small';

		if ($userCount > SubscriptionService::THRESHOLD_MEDIUM) {
			if ($userCount > SubscriptionService::THRESHOLD_LARGE) {
				$instanceSize = 'large';
			} else {
				$instanceSize = 'medium';
			}
		}

		$subscriptionKey = $this->appConfig->getValueString('support', 'subscription_key');
		$potentialSubscriptionKey = $this->appConfig->getValueString('support', 'potential_subscription_key');
		$subscriptionInfo = $this->appConfig->getValueArray('support', 'last_response', lazy: true);
		$lastError = $this->appConfig->getValueInt('support', 'last_error');
		// delete the invalid error, because there is no renewal happening
		if ($lastError === SubscriptionService::ERROR_FAILED_INVALID) {
			if ($subscriptionKey !== '') {
				$this->appConfig->setValueString('support', 'potential_subscription_key', $subscriptionKey);
			} else {
				$this->appConfig->deleteKey('support', 'potential_subscription_key');
			}
			$this->appConfig->deleteKey('support', 'last_error');
		} elseif ($lastError === SubscriptionService::ERROR_INVALID_SUBSCRIPTION_KEY) {
			$this->appConfig->deleteKey('support', 'last_error');
		}

		$now = new \DateTime();
		$subscriptionEndDate = new \DateTime($subscriptionInfo['endDate'] ?? 'now');
		if ($now > $subscriptionEndDate) {
			$years = 0;
			$months = 0;
			$days = 0;
			$weeks = 0;
		} else {
			$diff = $now->diff($subscriptionEndDate);
			$years = (int)$diff->format('%y');
			$months = (int)$diff->format('%m');
			$days = (int)$diff->format('%d');
			$weeks = floor($days / 7);

			/* run up to the next month for 4 weeks and more */
			if ($weeks > 3) {
				$months += 1;
				$weeks = 0;
				$days = 0;
			}
		}

		$specificSubscriptions = [];

		$collaboraEndDate = new \DateTime($subscriptionInfo['collabora']['endDate'] ?? 'yesterday');
		if ($now < $collaboraEndDate) {
			$specificSubscriptions[] = 'Collabora';
		}
		$talkEndDate = new \DateTime($subscriptionInfo['talk']['endDate'] ?? 'yesterday');
		if ($now < $talkEndDate) {
			$specificSubscriptions[] = 'Talk';
		}
		$groupwareEndDate = new \DateTime($subscriptionInfo['groupware']['endDate'] ?? 'yesterday');
		if ($now < $groupwareEndDate) {
			$specificSubscriptions[] = 'Groupware';
		}
		$allowedUsersCount = $subscriptionInfo['amountOfUsers'] ?? 0;
		$onlyCountActiveUsers = $subscriptionInfo['onlyCountActiveUsers'] ?? false;

		if ($allowedUsersCount === -1) {
			$isOverLimit = false;
		} elseif ($onlyCountActiveUsers) {
			$isOverLimit = $allowedUsersCount < $activeUserCount;
		} else {
			$isOverLimit = $allowedUsersCount < $userCount;
		}

		if (isset($subscriptionInfo['partnerContact']) && count($subscriptionInfo['partnerContact']) > 0) {
			$contactInfo = $subscriptionInfo['partnerContact'];
		} else {
			$contactInfo = $subscriptionInfo['accountManagerInfo'] ?? '';
		}

		$params = [
			'instanceSize' => $instanceSize,
			'userCount' => $userCount,
			'activeUserCount' => $activeUserCount,
			'subscriptionKey' => $subscriptionKey,
			'potentialSubscriptionKey' => $potentialSubscriptionKey,
			'lastError' => $lastError,
			'contactPerson' => $contactInfo,

			'subscriptionType' => $subscriptionInfo['level'] ?? '',
			'subscriptionUsers' => $allowedUsersCount,
			'onlyCountActiveUsers' => $onlyCountActiveUsers,
			'specificSubscriptions' => $specificSubscriptions,
			'extendedSupport' => $subscriptionInfo['extendedSupport'] ?? false,
			'expiryYears' => $years,
			'expiryMonths' => $months,
			'expiryWeeks' => $weeks,
			'expiryDays' => $days,

			'validSubscription' => ($years + $months + $days) > 0,
			'overLimit' => $isOverLimit,


			'showSubscriptionDetails' => !empty($subscriptionInfo),
			'showSubscriptionKeyInput' => empty($subscriptionInfo),
			'showCommunitySupportSection' => $instanceSize === 'small' && empty($subscriptionInfo),
			'showEnterpriseSupportSection' => $instanceSize !== 'small' && empty($subscriptionInfo),

			'subscriptionKeyUrl' => $this->urlGenerator->linkToRoute('support.api.setSubscriptionKey'),

			'offlineActivationData' => [
				'subscriptionKey' => $potentialSubscriptionKey,
				'instanceId' => $this->config->getSystemValueString('instanceid', ''),
				'userCount' => $userCount,
				'activeUserCount' => $activeUserCount,
				'version' => implode('.', $this->serverVersion->getVersion())
			],

			'subscriptionEndDate' => $subscriptionEndDate->format('Y-m-d'),
		];

		return new TemplateResponse('support', 'admin', $params);
	}

	#[\Override]
	public function getSection(): string {
		return 'support';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * keep the server setting at the top, right after "server settings"
	 */
	#[\Override]
	public function getPriority(): int {
		return 0;
	}

	#[\Override]
	public function getName(): ?string {
		return null; // Only one setting in this section
	}

	#[\Override]
	public function getAuthorizedAppConfig(): array {
		return [
			'support' => ['.*'],
		];
	}
}
