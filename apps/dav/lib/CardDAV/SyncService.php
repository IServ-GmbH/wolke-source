<?php

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Anna Larch <anna.larch@gmx.net>
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
namespace OCA\DAV\CardDAV;

use OCP\AppFramework\Db\TTransactional;
use OCP\AppFramework\Http;
use OCP\Http\Client\IClientService;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Xml\Response\MultiStatus;
use Sabre\DAV\Xml\Service;
use Sabre\VObject\Reader;
use function is_null;

class SyncService {

	use TTransactional;

	private CardDavBackend $backend;
	private IUserManager $userManager;
	private IDBConnection $dbConnection;
	private LoggerInterface $logger;
	private ?array $localSystemAddressBook = null;
	private Converter $converter;
	protected string $certPath;
	private IClientService $clientService;

	public function __construct(CardDavBackend $backend,
		IUserManager $userManager,
		IDBConnection $dbConnection,
		LoggerInterface $logger,
		Converter $converter,
		IClientService $clientService) {
		$this->backend = $backend;
		$this->userManager = $userManager;
		$this->logger = $logger;
		$this->converter = $converter;
		$this->certPath = '';
		$this->dbConnection = $dbConnection;
		$this->clientService = $clientService;
	}

	/**
	 * @throws \Exception
	 */
	public function syncRemoteAddressBook(string $url, string $userName, string $addressBookUrl, string $sharedSecret, ?string $syncToken, string $targetBookHash, string $targetPrincipal, array $targetProperties): string {
		// 1. create addressbook
		$book = $this->ensureSystemAddressBookExists($targetPrincipal, $targetBookHash, $targetProperties);
		$addressBookId = $book['id'];

		// 2. query changes
		try {
			$response = $this->requestSyncReport($url, $userName, $addressBookUrl, $sharedSecret, $syncToken);
		} catch (ClientExceptionInterface $ex) {
			if ($ex->getCode() === Http::STATUS_UNAUTHORIZED) {
				// remote server revoked access to the address book, remove it
				$this->backend->deleteAddressBook($addressBookId);
				$this->logger->error('Authorization failed, remove address book: ' . $url, ['app' => 'dav']);
				throw $ex;
			}
			$this->logger->error('Client exception:', ['app' => 'dav', 'exception' => $ex]);
			throw $ex;
		}

		// 3. apply changes
		// TODO: use multi-get for download
		foreach ($response['response'] as $resource => $status) {
			$cardUri = basename($resource);
			if (isset($status[200])) {
				$vCard = $this->download($url, $userName, $sharedSecret, $resource);
				$this->atomic(function () use ($addressBookId, $cardUri, $vCard) {
					$existingCard = $this->backend->getCard($addressBookId, $cardUri);
					if ($existingCard === false) {
						$this->backend->createCard($addressBookId, $cardUri, $vCard);
					} else {
						$this->backend->updateCard($addressBookId, $cardUri, $vCard);
					}
				}, $this->dbConnection);
			} else {
				$this->backend->deleteCard($addressBookId, $cardUri);
			}
		}

		return $response['token'];
	}

	/**
	 * @throws \Sabre\DAV\Exception\BadRequest
	 */
	public function ensureSystemAddressBookExists(string $principal, string $uri, array $properties): ?array {
		return $this->atomic(function () use ($principal, $uri, $properties) {
			$book = $this->backend->getAddressBooksByUri($principal, $uri);
			if (!is_null($book)) {
				return $book;
			}
			$this->backend->createAddressBook($principal, $uri, $properties);

			return $this->backend->getAddressBooksByUri($principal, $uri);
		}, $this->dbConnection);
	}

	public function ensureLocalSystemAddressBookExists(): ?array {
		return $this->ensureSystemAddressBookExists('principals/system/system', 'system', [
			'{' . Plugin::NS_CARDDAV . '}addressbook-description' => 'System addressbook which holds all users of this instance'
		]);
	}

	private function prepareUri(string $host, string $path): string {
		/*
		 * The trailing slash is important for merging the uris together.
		 *
		 * $host is stored in oc_trusted_servers.url and usually without a trailing slash.
		 *
		 * Example for a report request
		 *
		 * $host = 'https://server.internal/cloud'
		 * $path = 'remote.php/dav/addressbooks/system/system/system'
		 *
		 * Without the trailing slash, the webroot is missing:
		 * https://server.internal/remote.php/dav/addressbooks/system/system/system
		 *
		 * Example for a download request
		 *
		 * $host = 'https://server.internal/cloud'
		 * $path = '/cloud/remote.php/dav/addressbooks/system/system/system/Database:alice.vcf'
		 *
		 * The response from the remote usually contains the webroot already and must be normalized to:
		 * https://server.internal/cloud/remote.php/dav/addressbooks/system/system/system/Database:alice.vcf
		 */
		$host = rtrim($host, '/') . '/';

		$uri = \GuzzleHttp\Psr7\UriResolver::resolve(
			\GuzzleHttp\Psr7\Utils::uriFor($host),
			\GuzzleHttp\Psr7\Utils::uriFor($path)
		);

		return (string)$uri;
	}

	/**
	 * @throws ClientExceptionInterface
	 */
	protected function requestSyncReport(string $url, string $userName, string $addressBookUrl, string $sharedSecret, ?string $syncToken): array {
		$client = $this->clientService->newClient();
		$uri = $this->prepareUri($url, $addressBookUrl);

		$options = [
			'auth' => [$userName, $sharedSecret],
			'body' => $this->buildSyncCollectionRequestBody($syncToken),
			'headers' => ['Content-Type' => 'application/xml']
		];

		$response = $client->request(
			'REPORT',
			$uri,
			$options
		);

		$body = $response->getBody();
		assert(is_string($body));

		return $this->parseMultiStatus($body);
	}

	protected function download(string $url, string $userName, string $sharedSecret, string $resourcePath): string {
		$client = $this->clientService->newClient();
		$uri = $this->prepareUri($url, $resourcePath);

		$options = [
			'auth' => [$userName, $sharedSecret],
		];

		$response = $client->get(
			$uri,
			$options
		);

		return (string)$response->getBody();
	}

	private function buildSyncCollectionRequestBody(?string $syncToken): string {
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;
		$root = $dom->createElementNS('DAV:', 'd:sync-collection');
		$sync = $dom->createElement('d:sync-token', $syncToken ?? '');
		$prop = $dom->createElement('d:prop');
		$cont = $dom->createElement('d:getcontenttype');
		$etag = $dom->createElement('d:getetag');

		$prop->appendChild($cont);
		$prop->appendChild($etag);
		$root->appendChild($sync);
		$root->appendChild($prop);
		$dom->appendChild($root);
		return $dom->saveXML();
	}

	/**
	 * @param string $body
	 * @return array
	 * @throws \Sabre\Xml\ParseException
	 */
	private function parseMultiStatus($body) {
		$xml = new Service();

		/** @var MultiStatus $multiStatus */
		$multiStatus = $xml->expect('{DAV:}multistatus', $body);

		$result = [];
		foreach ($multiStatus->getResponses() as $response) {
			$result[$response->getHref()] = $response->getResponseProperties();
		}

		return ['response' => $result, 'token' => $multiStatus->getSyncToken()];
	}

	/**
	 * @param IUser $user
	 */
	public function updateUser(IUser $user): void {
		$systemAddressBook = $this->getLocalSystemAddressBook();
		$addressBookId = $systemAddressBook['id'];

		$cardId = self::getCardUri($user);
		if ($user->isEnabled()) {
			$this->atomic(function () use ($addressBookId, $cardId, $user) {
				$card = $this->backend->getCard($addressBookId, $cardId);
				if ($card === false) {
					$vCard = $this->converter->createCardFromUser($user);
					if ($vCard !== null) {
						$this->backend->createCard($addressBookId, $cardId, $vCard->serialize(), false);
					}
				} else {
					$vCard = $this->converter->createCardFromUser($user);
					if (is_null($vCard)) {
						$this->backend->deleteCard($addressBookId, $cardId);
					} else {
						$this->backend->updateCard($addressBookId, $cardId, $vCard->serialize());
					}
				}
			}, $this->dbConnection);
		} else {
			$this->backend->deleteCard($addressBookId, $cardId);
		}
	}

	/**
	 * @param IUser|string $userOrCardId
	 */
	public function deleteUser($userOrCardId) {
		$systemAddressBook = $this->getLocalSystemAddressBook();
		if ($userOrCardId instanceof IUser) {
			$userOrCardId = self::getCardUri($userOrCardId);
		}
		$this->backend->deleteCard($systemAddressBook['id'], $userOrCardId);
	}

	/**
	 * @return array|null
	 */
	public function getLocalSystemAddressBook() {
		if (is_null($this->localSystemAddressBook)) {
			$this->localSystemAddressBook = $this->ensureLocalSystemAddressBookExists();
		}

		return $this->localSystemAddressBook;
	}

	/**
	 * @return void
	 */
	public function syncInstance(?\Closure $progressCallback = null) {
		$systemAddressBook = $this->getLocalSystemAddressBook();
		$this->userManager->callForAllUsers(function ($user) use ($systemAddressBook, $progressCallback) {
			$this->updateUser($user);
			if (!is_null($progressCallback)) {
				$progressCallback();
			}
		});

		// remove no longer existing
		$allCards = $this->backend->getCards($systemAddressBook['id']);
		foreach ($allCards as $card) {
			$vCard = Reader::read($card['carddata']);
			$uid = $vCard->UID->getValue();
			// load backend and see if user exists
			if (!$this->userManager->userExists($uid)) {
				$this->deleteUser($card['uri']);
			}
		}
	}

	/**
	 * @param IUser $user
	 * @return string
	 */
	public static function getCardUri(IUser $user): string {
		return $user->getBackendClassName() . ':' . $user->getUID() . '.vcf';
	}
}
