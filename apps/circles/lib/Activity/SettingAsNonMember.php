<?php

declare(strict_types=1);

/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2023
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Circles\Activity;

use OCP\Activity\ISetting;
use OCP\IL10N;

class SettingAsNonMember implements ISetting {
	public function __construct(
		protected IL10N $l10n
	) {
	}


	/**
	 * @return string Lowercase a-z and underscore only identifier
	 * @since 11.0.0
	 */
	public function getIdentifier() {
		return 'circles_as_non_member';
	}


	/**
	 * @return string A translated string
	 * @since 11.0.0
	 */
	public function getName() {
		return $this->l10n->t('On global events happening in any <strong>Circle</strong>');
	}


	/**
	 * @return int whether the filter should be rather on the top or bottom of
	 * the admin section. The filters are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 * @since 11.0.0
	 */
	public function getPriority() {
		return 60;
	}


	/**
	 * @return bool True when the option can be changed for the stream
	 * @since 11.0.0
	 */
	public function canChangeStream() {
		return true;
	}


	/**
	 * @return bool True when the option can be changed for the stream
	 * @since 11.0.0
	 */
	public function isDefaultEnabledStream() {
		return false;
	}


	/**
	 * @return bool True when the option can be changed for the mail
	 * @since 11.0.0
	 */
	public function canChangeMail() {
		return true;
	}


	/**
	 * @return bool True when the option can be changed for the stream
	 * @since 11.0.0
	 */
	public function isDefaultEnabledMail() {
		return false;
	}
}
