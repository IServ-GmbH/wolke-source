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


namespace OCA\Circles\Service;

use OCA\Circles\AppInfo\Application;
use OCA\Circles\Db\CircleRequest;
use OCA\Circles\Db\MemberRequest;
use OCA\Circles\Exceptions\CircleNameTooShortException;
use OCA\Circles\Exceptions\CircleNotFoundException;
use OCA\Circles\Exceptions\FederatedEventException;
use OCA\Circles\Exceptions\FederatedItemException;
use OCA\Circles\Exceptions\InitiatorNotConfirmedException;
use OCA\Circles\Exceptions\InitiatorNotFoundException;
use OCA\Circles\Exceptions\MembersLimitException;
use OCA\Circles\Exceptions\OwnerNotFoundException;
use OCA\Circles\Exceptions\RemoteInstanceException;
use OCA\Circles\Exceptions\RemoteNotFoundException;
use OCA\Circles\Exceptions\RemoteResourceNotFoundException;
use OCA\Circles\Exceptions\RequestBuilderException;
use OCA\Circles\Exceptions\UnknownRemoteException;
use OCA\Circles\FederatedItems\CircleConfig;
use OCA\Circles\FederatedItems\CircleCreate;
use OCA\Circles\FederatedItems\CircleDestroy;
use OCA\Circles\FederatedItems\CircleEdit;
use OCA\Circles\FederatedItems\CircleJoin;
use OCA\Circles\FederatedItems\CircleLeave;
use OCA\Circles\FederatedItems\CircleSetting;
use OCA\Circles\IEntity;
use OCA\Circles\IFederatedUser;
use OCA\Circles\Model\Circle;
use OCA\Circles\Model\Federated\FederatedEvent;
use OCA\Circles\Model\FederatedUser;
use OCA\Circles\Model\ManagedModel;
use OCA\Circles\Model\Member;
use OCA\Circles\Model\Probes\CircleProbe;
use OCA\Circles\Model\Probes\MemberProbe;
use OCA\Circles\StatusCode;
use OCA\Circles\Tools\Model\SimpleDataStore;
use OCA\Circles\Tools\Traits\TArrayTools;
use OCA\Circles\Tools\Traits\TNCLogger;
use OCA\Circles\Tools\Traits\TStringTools;
use OCP\IL10N;
use OCP\Security\IHasher;

class CircleService {
	use TArrayTools;
	use TStringTools;
	use TNCLogger;


	/** @var IL10N */
	private $l10n;

	/** @var IHasher */
	private $hasher;

	/** @var CircleRequest */
	private $circleRequest;

	/** @var MemberRequest */
	private $memberRequest;

	/** @var RemoteStreamService */
	private $remoteStreamService;

	/** @var FederatedUserService */
	private $federatedUserService;

	/** @var FederatedEventService */
	private $federatedEventService;

	/** @var MemberService */
	private $memberService;

	/** @var PermissionService */
	private $permissionService;

	/** @var ConfigService */
	private $configService;


	/**
	 * @param IL10N $l10n
	 * @param IHasher $hasher
	 * @param CircleRequest $circleRequest
	 * @param MemberRequest $memberRequest
	 * @param RemoteStreamService $remoteStreamService
	 * @param FederatedUserService $federatedUserService
	 * @param FederatedEventService $federatedEventService
	 * @param MemberService $memberService
	 * @param PermissionService $permissionService
	 * @param ConfigService $configService
	 */
	public function __construct(
		IL10N $l10n,
		IHasher $hasher,
		CircleRequest $circleRequest,
		MemberRequest $memberRequest,
		RemoteStreamService $remoteStreamService,
		FederatedUserService $federatedUserService,
		FederatedEventService $federatedEventService,
		MemberService $memberService,
		PermissionService $permissionService,
		ConfigService $configService
	) {
		$this->l10n = $l10n;
		$this->hasher = $hasher;
		$this->circleRequest = $circleRequest;
		$this->memberRequest = $memberRequest;
		$this->remoteStreamService = $remoteStreamService;
		$this->federatedUserService = $federatedUserService;
		$this->federatedEventService = $federatedEventService;
		$this->memberService = $memberService;
		$this->permissionService = $permissionService;
		$this->configService = $configService;

		$this->setup('app', Application::APP_ID);
	}


	/**
	 * @param string $name
	 * @param FederatedUser|null $owner
	 * @param bool $personal
	 * @param bool $local
	 *
	 * @return array
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws RequestBuilderException
	 * @throws CircleNameTooShortException
	 */
	public function create(
		string $name,
		?FederatedUser $owner = null,
		bool $personal = false,
		bool $local = false
	): array {
		$this->federatedUserService->mustHaveCurrentUser();
		if (is_null($owner)) {
			$owner = $this->federatedUserService->getCurrentUser();
		}

		if (is_null($owner)) {
			$owner = $this->federatedUserService->getCurrentApp();
		}

		if (is_null($owner)) {
			throw new OwnerNotFoundException('owner not defined');
		}

		$circle = new Circle();
		$circle->setName($this->cleanCircleName($name))
			   ->setSingleId($this->token(ManagedModel::ID_LENGTH))
			   ->setSource(Member::TYPE_CIRCLE);

		if (strlen($circle->getName()) < 3) {
			throw new CircleNameTooShortException('Circle name is too short');
		}

		if ($personal) {
			$circle->setConfig(Circle::CFG_PERSONAL);
		}

		if ($local) {
			$circle->addConfig(Circle::CFG_LOCAL);
		}

		$this->confirmName($circle);
		$this->permissionService->confirmAllowedCircleTypes($circle);

		$member = new Member();
		$member->importFromIFederatedUser($owner);
		$member->setId($this->token(ManagedModel::ID_LENGTH))
			   ->setCircleId($circle->getSingleId())
			   ->setLevel(Member::LEVEL_OWNER)
			   ->setStatus(Member::STATUS_MEMBER);

		$this->federatedUserService->setMemberPatron($member);

		$circle->setOwner($member)
			   ->setInitiator($member);

		$event = new FederatedEvent(CircleCreate::class);
		$event->setCircle($circle);
		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}


	/**
	 * @param string $circleId
	 *
	 * @return array
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function destroy(string $circleId): array {
		$this->federatedUserService->mustHaveCurrentUser();

		$circle = $this->getCircle($circleId);

		$event = new FederatedEvent(CircleDestroy::class);
		$event->setCircle($circle);
		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}


	/**
	 * @param string $circleId
	 * @param int $config
	 * @param bool $superSession
	 *
	 * @return array
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function updateConfig(string $circleId, int $config): array {
		$this->federatedUserService->mustHaveCurrentUser();
		$circle = $this->getCircle($circleId);

		$event = new FederatedEvent(CircleConfig::class);
		$event->setCircle($circle);
		$event->setParams(
			new SimpleDataStore(
				[
					'config' => $config,
					'superSession' => $this->federatedUserService->canBypassCurrentUserCondition()
				]
			)
		);

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}


	/**
	 * if $value is null, setting is unset
	 *
	 * @param string $circleId
	 * @param string $setting
	 * @param string|null $value
	 *
	 * @return array
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function updateSetting(string $circleId, string $setting, ?string $value): array {
		$circle = $this->getCircle($circleId);

		if (strtolower($setting) === 'password_single' && !is_null($value)) {
			$value = $this->hasher->hash($value);
		}

		$event = new FederatedEvent(CircleSetting::class);
		$event->setCircle($circle);
		$event->setParams(
			new SimpleDataStore(
				[
					'setting' => $setting,
					'value' => $value,
					'unset' => is_null($value)
				]
			)
		);

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}


	/**
	 * @param string $circleId
	 * @param string $name
	 *
	 * @return array
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function updateName(string $circleId, string $name): array {
		$circle = $this->getCircle($circleId);

		$event = new FederatedEvent(CircleEdit::class);
		$event->setCircle($circle);
		$event->setParams(new SimpleDataStore(['name' => $name]));

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}

	/**
	 * @param string $circleId
	 * @param string $description
	 *
	 * @return array
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function updateDescription(string $circleId, string $description): array {
		$circle = $this->getCircle($circleId);

		$event = new FederatedEvent(CircleEdit::class);
		$event->setCircle($circle);
		$event->setParams(new SimpleDataStore(['description' => $description]));

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}


	/**
	 * @param string $circleId
	 *
	 * @return array
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws UnknownRemoteException
	 * @throws RequestBuilderException
	 */
	public function circleJoin(string $circleId): array {
		$this->federatedUserService->mustHaveCurrentUser();

		$probe = new CircleProbe();
		$probe->includeNonVisibleCircles()
			  ->emulateVisitor();

		$circle = $this->circleRequest->getCircle(
			$circleId,
			$this->federatedUserService->getCurrentUser(),
			$probe
		);

		if (!$circle->getInitiator()->hasInvitedBy()) {
			$this->federatedUserService->setMemberPatron($circle->getInitiator());
		}

		$event = new FederatedEvent(CircleJoin::class);
		$event->setCircle($circle);

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}


	/**
	 * @param string $circleId
	 * @param bool $force
	 *
	 * @return array
	 * @throws CircleNotFoundException
	 * @throws FederatedEventException
	 * @throws FederatedItemException
	 * @throws InitiatorNotConfirmedException
	 * @throws InitiatorNotFoundException
	 * @throws OwnerNotFoundException
	 * @throws RemoteInstanceException
	 * @throws RemoteNotFoundException
	 * @throws RemoteResourceNotFoundException
	 * @throws RequestBuilderException
	 * @throws UnknownRemoteException
	 */
	public function circleLeave(string $circleId, bool $force = false): array {
		$this->federatedUserService->mustHaveCurrentUser();

		$probe = new CircleProbe();
		$probe->includeNonVisibleCircles()
			  ->emulateVisitor();

		$circle = $this->circleRequest->getCircle(
			$circleId,
			$this->federatedUserService->getCurrentUser(),
			$probe
		);

		$event = new FederatedEvent(CircleLeave::class);
		$event->setCircle($circle);
		$event->getParams()->sBool('force', $force);

		$this->federatedEventService->newEvent($event);

		return $event->getOutcome();
	}


	/**
	 * @param string $circleId
	 * @param CircleProbe|null $probe
	 *
	 * @return Circle
	 * @throws CircleNotFoundException
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getCircle(
		string $circleId,
		?CircleProbe $probe = null
	): Circle {
		$this->federatedUserService->mustHaveCurrentUser();

		return $this->circleRequest->getCircle(
			$circleId,
			$this->federatedUserService->getCurrentEntity(),
			$probe
		);
	}


	/**
	 * @param CircleProbe|null $probe
	 *
	 * @return Circle[]
	 * @throws InitiatorNotFoundException
	 * @throws RequestBuilderException
	 */
	public function getCircles(CircleProbe $probe): array {
		$this->federatedUserService->mustHaveCurrentUser();

		return $this->circleRequest->getCircles(
			$this->federatedUserService->getCurrentUser(),
			$probe
		);
	}


	/**
	 * @param Circle $circle
	 *
	 * @throws RequestBuilderException
	 */
	public function confirmName(Circle $circle): void {
		if ($circle->isConfig(Circle::CFG_SYSTEM)
			|| $circle->isConfig(Circle::CFG_SINGLE)) {
			return;
		}

		$this->confirmDisplayName($circle);
		$this->generateSanitizedName($circle);
	}

	/**
	 * @param Circle $circle
	 *
	 * @throws RequestBuilderException
	 */
	private function confirmDisplayName(Circle $circle) {
		$baseDisplayName = $circle->getName();

		$i = 1;
		while (true) {
			$testDisplayName = $baseDisplayName . (($i > 1) ? ' (' . $i . ')' : '');
			$test = new Circle();
			$test->setDisplayName($testDisplayName);

			try {
				$stored = $this->circleRequest->searchCircle($test);
				if ($stored->getSingleId() === $circle->getSingleId()) {
					throw new CircleNotFoundException();
				}
			} catch (CircleNotFoundException $e) {
				$circle->setDisplayName($testDisplayName);

				return;
			}

			$i++;
		}
	}


	/**
	 * @param Circle $circle
	 *
	 * @throws RequestBuilderException
	 */
	public function generateSanitizedName(Circle $circle) {
		$baseSanitizedName = $this->sanitizeName($circle->getName());
		if ($baseSanitizedName === '') {
			$baseSanitizedName = substr($circle->getSingleId(), 0, 3);
		}

		$i = 1;
		while (true) {
			$testSanitizedName = $baseSanitizedName . (($i > 1) ? ' (' . $i . ')' : '');

			$test = new Circle();
			$test->setSanitizedName($testSanitizedName);

			try {
				$stored = $this->circleRequest->searchCircle($test);
				if ($stored->getSingleId() === $circle->getSingleId()) {
					throw new CircleNotFoundException();
				}
			} catch (CircleNotFoundException $e) {
				$circle->setSanitizedName($testSanitizedName);

				return;
			}

			$i++;
		}
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function sanitizeName(string $name): string {
		// replace '/' with '-' to prevent directory traversal
		// replacing instead of stripping seems the better tradeoff here
		$sanitized = str_replace('/', '-', $name);

		// remove characters which are illegal on Windows (includes illegal characters on Unix/Linux)
		// see also \OC\Files\Storage\Common::verifyPosixPath(...)
		/** @noinspection CascadeStringReplacementInspection */
		$sanitized = str_replace(['*', '|', '\\', ':', '"', '<', '>', '?'], '', $sanitized);

		// remove leading+trailing spaces and dots to prevent hidden files
		return trim($sanitized, ' .');
	}


	/**
	 * @param Circle $circle
	 *
	 * @throws MembersLimitException
	 */
	public function confirmCircleNotFull(Circle $circle): void {
		if ($this->isCircleFull($circle)) {
			throw new MembersLimitException(StatusCode::$MEMBER_ADD[121], 121);
		}
	}


	/**
	 * @param Circle $circle
	 *
	 * @return bool
	 * @throws RequestBuilderException
	 */
	public function isCircleFull(Circle $circle): bool {
		$filterMember = new Member();
		$filterMember->setLevel(Member::LEVEL_MEMBER);
		$probe = new MemberProbe();
		$probe->setFilterMember($filterMember);

		$members = $this->memberRequest->getMembers($circle->getSingleId(), null, $probe);

		$limit = $this->getInt('members_limit', $circle->getSettings());
		if ($limit === 0) {
			$limit = $this->configService->getAppValueInt(ConfigService::MEMBERS_LIMIT);
		}
		if ($limit === -1) {
			return false;
		}

		return (sizeof($members) >= $limit);
	}


	/**
	 * @param string $name
	 *
	 * @return string
	 */
	public function cleanCircleName(string $name): string {
		$name = preg_replace('/\s+/', ' ', $name);

		return trim($name);
	}


	/**
	 * @param IEntity $entity
	 *
	 * @return string
	 */
	public function getDefinition(IEntity $entity): string {
		if ($entity instanceof Circle) {
			return $this->getDefinitionCircle($entity);
		}
		if ($entity instanceof IFederatedUser) {
			return $this->getDefinitionUser($entity);
		}

		return '';
	}

	/**
	 * @param Circle $circle
	 *
	 * @return string
	 */
	public function getDefinitionCircle(Circle $circle): string {
		$source = Circle::$DEF_SOURCE[$circle->getSource()];
		if ($circle->isConfig(Circle::CFG_NO_OWNER)
			|| $circle->isConfig(Circle::CFG_SINGLE)) {
			return $this->l10n->t('%s', [$source]);
		}

		if ($circle->isConfig(Circle::CFG_PERSONAL)) {
			return $this->l10n->t('Personal Circle');
		}

		return $this->l10n->t(
			'%s owned by %s',
			[
				$source,
				$this->configService->displayFederatedUser($circle->getOwner(), true)
			]
		);
	}

	/**
	 * @param IFederatedUser $federatedUser
	 *
	 * @return string
	 */
	public function getDefinitionUser(IFederatedUser $federatedUser): string {
		return $this->l10n->t('%s', [Circle::$DEF_SOURCE[$federatedUser->getUserType()]]);
	}
}
