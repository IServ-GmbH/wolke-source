<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OCP\UserStatus;

/**
 * Interface IProvider
 *
 * @since 20.0.0
 */
interface IProvider {
	/**
	 * Gets the statuses for all users in $users
	 *
	 * @param string[] $userIds
	 * @return array<string, IUserStatus> array key being the userid, users without a status will not be in the returned array
	 * @since 20.0.0
	 */
	public function getUserStatuses(array $userIds):array;
}
