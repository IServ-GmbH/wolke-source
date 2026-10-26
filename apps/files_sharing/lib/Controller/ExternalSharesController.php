<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Controller;

use OCA\Files_Sharing\BackgroundJob\ExternalShareScanJob;
use OCA\Files_Sharing\External\ExternalShare;
use OCA\Files_Sharing\External\Manager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\BackgroundJob\IJobList;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IRequest;
use OCP\Share\IShare;

/**
 * Class ExternalSharesController
 *
 * @package OCA\Files_Sharing\Controller
 */
class ExternalSharesController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly Manager $externalManager,
		private IJobList $jobList,
		private ISearch $collaboratorSearch,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		return new JSONResponse(array_map($this->addOwnerDisplayName(...), $this->externalManager->getOpenShares()));
	}

	/**
	 * The incoming share dialog shows the owner's display name instead of the uid.
	 */
	private function addOwnerDisplayName(ExternalShare $share): array {
		$shareData = $share->jsonSerialize();
		[$ownerResults,] = $this->collaboratorSearch->search($share->getOwner(), [IShare::TYPE_REMOTE], false, 1, 0);
		if (isset($ownerResults['remotes'][0]['name'])) {
			$shareData['ownerDisplayName'] = $ownerResults['remotes'][0]['name'];
		}
		return $shareData;
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 */
	#[NoAdminRequired]
	public function create(string $id): JSONResponse {
		$externalShare = $this->externalManager->getShare($id);
		if ($externalShare !== false) {
			$this->externalManager->acceptShare($externalShare);
			$this->jobList->add(ExternalShareScanJob::class, [$externalShare->getUser(), $externalShare->getMountpoint()]);
		}
		return new JSONResponse();
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 */
	#[NoAdminRequired]
	public function destroy(string $id): JSONResponse {
		$externalShare = $this->externalManager->getShare($id);
		if ($externalShare !== false) {
			$this->externalManager->declineShare($externalShare);
		}
		return new JSONResponse();
	}
}
