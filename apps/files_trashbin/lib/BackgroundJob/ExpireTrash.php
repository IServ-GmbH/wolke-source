<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_Trashbin\BackgroundJob;

use OCA\Files_Trashbin\Expiration;
use OCA\Files_Trashbin\Helper;
use OCA\Files_Trashbin\Trashbin;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IAppConfig;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class ExpireTrash extends TimedJob {

	public function __construct(
		private IAppConfig $appConfig,
		private IUserManager $userManager,
		private Expiration $expiration,
		private LoggerInterface $logger,
		ITimeFactory $time
	) {
		parent::__construct($time);
		// Run once per 30 minutes
		$this->setInterval(60 * 30);
	}

	protected function run($argument) {
		$backgroundJob = $this->appConfig->getValueString('files_trashbin', 'background_job_expire_trash', 'yes');
		if ($backgroundJob === 'no') {
			return;
		}

		$maxAge = $this->expiration->getMaxAgeAsTimestamp();
		if (!$maxAge) {
			return;
		}

		$stopTime = time() + 60 * 30; // Stops after 30 minutes.
		$offset = $this->appConfig->getValueInt('files_trashbin', 'background_job_expire_trash_offset', 0);
		$users = $this->userManager->getSeenUsers($offset);

		foreach ($users as $user) {
			try {
				$uid = $user->getUID();
				if (!$this->setupFS($uid)) {
					continue;
				}
				$dirContent = Helper::getTrashFiles('/', $uid, 'mtime');
				Trashbin::deleteExpiredFiles($dirContent, $uid);
			} catch (\Throwable $e) {
				$this->logger->error('Error while expiring trashbin for user ' . $user->getUID(), ['exception' => $e]);
			}

			$offset++;

			if ($stopTime < time()) {
				$this->appConfig->setValueInt('files_trashbin', 'background_job_expire_trash_offset', $offset);
				\OC_Util::tearDownFS();
				return;
			}
		}

		$this->appConfig->setValueInt('files_trashbin', 'background_job_expire_trash_offset', 0);
		\OC_Util::tearDownFS();
	}

	/**
	 * Act on behalf on trash item owner
	 */
	protected function setupFS(string $user): bool {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($user);

		// Check if this user has a trashbin directory
		$view = new \OC\Files\View('/' . $user);
		if (!$view->is_dir('/files_trashbin/files')) {
			return false;
		}

		return true;
	}
}
