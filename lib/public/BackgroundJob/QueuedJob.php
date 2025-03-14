<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCP\BackgroundJob;

use OCP\ILogger;

/**
 * Simple base class for a one time background job
 *
 * @since 15.0.0
 */
abstract class QueuedJob extends Job {
	/**
	 * Run the job, then remove it from the joblist
	 *
	 * @param IJobList $jobList
	 * @param ILogger|null $logger
	 *
	 * @since 15.0.0
	 * @deprecated since 25.0.0 Use start() instead. This method will be removed
	 * with the ILogger interface
	 */
	final public function execute($jobList, ILogger $logger = null) {
		$this->start($jobList);
	}

	/**
	 * Run the job, then remove it from the joblist
	 *
	 * @since 25.0.0
	 */
	final public function start(IJobList $jobList): void {
		if ($this->id) {
			$jobList->removeById($this->id);
		} else {
			$jobList->remove($this, $this->argument);
		}
		parent::start($jobList);
	}
}
