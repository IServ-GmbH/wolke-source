<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use OCP\IConfig;
use OCP\IRequest;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;

/**
 * Class BlockLegacyClientPlugin is used to detect old legacy sync clients and
 * returns a 403 status to those clients
 *
 * @package OCA\DAV\Connector\Sabre
 */
class BlockLegacyClientPlugin extends ServerPlugin {
	protected ?Server $server = null;
	protected IConfig $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * @return void
	 */
	public function initialize(Server $server) {
		$this->server = $server;
		$this->server->on('beforeMethod:*', [$this, 'beforeHandler'], 200);
	}

	/**
	 * Detects all unsupported clients and throws a \Sabre\DAV\Exception\Forbidden
	 * exception which will result in a 403 to them.
	 * @param RequestInterface $request
	 * @throws \Sabre\DAV\Exception\Forbidden If the client version is not supported
	 */
	public function beforeHandler(RequestInterface $request) {
		$userAgent = $request->getHeader('User-Agent');
		if ($userAgent === null) {
			return;
		}

		$minimumSupportedDesktopVersion = $this->config->getSystemValue('minimum.supported.desktop.version', '2.3.0');
		preg_match(IRequest::USER_AGENT_CLIENT_DESKTOP, $userAgent, $versionMatches);
		if (isset($versionMatches[1]) &&
			version_compare($versionMatches[1], $minimumSupportedDesktopVersion) === -1) {
			throw new \Sabre\DAV\Exception\Forbidden('Unsupported client version.');
		}

		if (!$this->config->getSystemValue('iserv_disable_external_clients', false)) {
			//early return if we shouldn't block
			return;
		}

		// Now block all CalDav requests
		// we block here and in the BlockClientMiddleware as well, bc if the user somehow got behind the login flow
		//     e.g. the user was already logged in, when the blockade was activated

		// List of regexes for all clients that get blocked
		$blockedClientRegexes = [
			IRequest::USER_AGENT_CLIENT_ANDROID,
			IRequest::USER_AGENT_CLIENT_IOS,
			IRequest::USER_AGENT_TALK_DESKTOP,
			IRequest::USER_AGENT_TALK_IOS,
			IRequest::USER_AGENT_OUTLOOK_ADDON,
			IRequest::USER_AGENT_THUNDERBIRD_ADDON,
			IRequest::USER_AGENT_CLIENT_DESKTOP // block desktop client here as well
		];

		foreach ($blockedClientRegexes as $regex) {
			if (preg_match($regex, $userAgent)) {
				$app = $this->config->getSystemValue('iserv_app_name', 'Wolke');
				throw new \Sabre\DAV\Exception\Forbidden("Access to $app via an external client is forbidden. Please visit $app in a browser or the IServ app.");
			}
		}
	}
}
