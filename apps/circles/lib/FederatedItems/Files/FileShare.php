<?php

declare(strict_types=1);


/**
 * Circles - Bring cloud-users closer together.
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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


namespace OCA\Circles\FederatedItems\Files;

use OCA\Circles\Tools\Exceptions\InvalidItemException;
use OCA\Circles\Tools\Exceptions\UnknownTypeException;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
use OCA\Circles\Db\MountRequest;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\IFederatedItem;
use OCA\Circles\IFederatedItemAsyncProcess;
use OCA\Circles\IFederatedItemHighSeverity;
use OCA\Circles\IFederatedItemMemberEmpty;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\Mount;
use OCA\Circles\Model\ShareWrapper;
use OCA\Circles\Service\ConfigService;
use OCA\Circles\Service\EventService;

/**
 * Class FileShare
 *
 * @package OCA\Circles\FederatedItems\Files
 */
class FileShare implements
	IFederatedItem,
	IFederatedItemHighSeverity,
	IFederatedItemAsyncProcess,
	IFederatedItemMemberEmpty {
	use TStringTools;
	use TNCLogger;


	/** @var MountRequest */
	private $mountRequest;

	/** @var EventService */
	private $eventService;

	/** @var ConfigService */
	private $configService;


	/**
	 * FileShare constructor.
	 *
	 * @param MountRequest $mountRequest
	 * @param EventService $eventService
	 * @param ConfigService $configService
	 */
	public function __construct(
		MountRequest $mountRequest,
		EventService $eventService,
		ConfigService $configService
	) {
		$this->mountRequest = $mountRequest;
		$this->eventService = $eventService;
		$this->configService = $configService;
	}


	/**
	 * @param FederatedEvent $event
	 */
	public function verify(FederatedEvent $event): void {
		// TODO: check (origin of file ?) and improve
		// TODO: Use a share lock

		$this->eventService->fileSharePreparing($event);
	}


	/**
	 * @param FederatedEvent $event
	 *
	 * @throws CircleNotFoundException
	 * @throws InvalidItemException
	 * @throws UnknownTypeException
	 */
	public function manage(FederatedEvent $event): void {
		$mount = null;
		if (!$this->configService->isLocalInstance($event->getOrigin())) {
			/** @var ShareWrapper $wrappedShare */
			$wrappedShare = $event->getParams()->gObj('wrappedShare', ShareWrapper::class);

			$mount = new Mount();
			$mount->fromShare($wrappedShare);
			$mount->setMountId($this->token(15));

			$this->mountRequest->save($mount);
		}

		$this->eventService->fileShareCreating($event, $mount);

//		$this->eventService->federatedShareCreated($wrappedShare, $mount);


//		$this->eventService->fileSharing($event);

//		$this->mountRequest->create($mount);
//		$circle = $event->getDeprecatedCircle();
//
//		// if event is not local, we create a federated file to the right instance of Nextcloud, using the right token
//		if (!$this->configService->isLocalInstance($event->getSource())) {
//			try {
//				$share = $this->getShareFromData($event->getData());
//			} catch (Exception $e) {
//				return;
//			}
//
//			$data = $event->getData();
//			$token = $data->g('gs_federated');
//			$filename = $data->g('gs_filename');
//
//			$gsShare = new GSShare($share->getSharedWith(), $token);
//			$gsShare->setOwner($share->getShareOwner());
//			$gsShare->setInstance($event->getSource());
//			$gsShare->setParent(-1);
//			$gsShare->setMountPoint($filename);
//
//			$this->gsSharesRequest->create($gsShare);
//		} else {
//			// if the event is local, we send mail to mail-as-members
//			$members = $this->membersRequest->forceGetMembers(
//				$circle->getUniqueId(), DeprecatedMember::LEVEL_MEMBER, DeprecatedMember::TYPE_MAIL, true
//			);
//
//			foreach ($members as $member) {
//				$this->sendShareToContact($event, $circle, $member->getMemberId(), [$member->getUserId()]);
//			}
//		}
//
//		// we also fill the event's result for further things, like contact-as-members
//		$members = $this->membersRequest->forceGetMembers(
//			$circle->getUniqueId(), DeprecatedMember::LEVEL_MEMBER, DeprecatedMember::TYPE_CONTACT, true
//		);
//
//		$accounts = [];
//		foreach ($members as $member) {
//			if ($member->getInstance() === '') {
//				$accounts[] = $this->miscService->getInfosFromContact($member);
//			}
//		}
//
//		$event->setResult(new SimpleDataStore(['contacts' => $accounts]));
	}


	/**
	 * @param FederatedEvent $event
	 * @param array $results
	 */
	public function result(FederatedEvent $event, array $results): void {
		$this->eventService->fileShareCreated($event, $results);

//		$event = null;
//		$contacts = [];
//		foreach (array_keys($events) as $instance) {
//			$event = $events[$instance];
//			$contacts = array_merge(
//				$contacts, $event->getResult()
//								 ->gArray('contacts')
//			);
//		}
//
//		if ($event === null || !$event->hasCircle()) {
//			return;
//		}
//
//		$circle = $event->getDeprecatedCircle();
//
//		foreach ($contacts as $contact) {
//			$this->sendShareToContact($event, $circle, $contact['memberId'], $contact['emails']);
//		}
	}
}
