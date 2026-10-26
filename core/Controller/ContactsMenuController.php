<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use Exception;
use OC\Contacts\ContactsMenu\Manager;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Teams\ITeamManager;

class ContactsMenuController extends Controller {
	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private Manager $manager,
		private ITeamManager $teamManager,
		private IAppManager $appManager,
	) {
		parent::__construct('core', $request);
	}

	/**
	 * @return \JsonSerializable[]
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/contactsmenu/contacts')]
	public function index(?string $filter = null, ?string $teamId = null): array {
		return [
			'contacts' => [],
			'contactsAppEnabled' => $this->appManager->isEnabledForUser('contacts', $this->userSession->getUser()),
		];
	}

	/**
	 * @return JSONResponse|\JsonSerializable
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/contactsmenu/findOne')]
	public function findOne(int $shareType, string $shareWith) {
		return new JSONResponse([], Http::STATUS_NOT_FOUND);
	}

	/**
	 * @return \JsonSerializable[]
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/contactsmenu/teams')]
	public function getTeams(): array {
		return $this->teamManager->getTeamsForUser($this->userSession->getUser()->getUID());
	}
}
