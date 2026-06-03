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

class ContactsMenuController extends Controller {
	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private Manager $manager,
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
	public function index(?string $filter = null): array {
        $user = $this->userSession->getUser();
        $contactsEnabled = $this->appManager->isEnabledForUser('contacts', $user);

        return [
            'contacts' => [],
            'contactsAppEnabled' => $contactsEnabled,
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
}
