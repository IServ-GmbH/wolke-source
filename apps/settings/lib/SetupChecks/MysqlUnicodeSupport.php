<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class MysqlUnicodeSupport implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('MySQL unicode support');
	}

	public function getCategory(): string {
		return 'database';
	}

	public function run(): SetupResult {
		if ($this->config->getSystemValueString('dbtype') !== 'mysql') {
			return SetupResult::success($this->l10n->t('You are not using MySQL'));
		}
		if ($this->config->getSystemValueBool('mysql.utf8mb4', false)) {
			return SetupResult::success($this->l10n->t('MySQL is used as database and does support 4-byte characters'));
		} else {
			return SetupResult::warning(
				$this->l10n->t('MySQL is used as database but does not support 4-byte characters. To be able to handle 4-byte characters (like emojis) without issues in filenames or comments for example it is recommended to enable the 4-byte support in MySQL.'),
				$this->urlGenerator->linkToDocs('admin-mysql-utf8mb4'),
			);
		}
	}
}
