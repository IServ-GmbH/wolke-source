<?php

namespace OC\Core\Middleware;

use OCP\AppFramework\Middleware;
use OCP\IRequest;
use OCP\IConfig;
use Sabre\DAV\Exception\Forbidden;

class BlockClientMiddleware extends Middleware {

	public function __construct(
		private IRequest $request,
		private IConfig $config,
	) {}

	public function beforeController($controller, $methodName) {
		if (!$this->config->getSystemValue('iserv_disable_external_clients', false)) {
			return;
		}

		$userAgent = $this->request->getHeader('User-Agent') ?? '';

		// List of regexes for all clients that get login requests blocked
		$blockedClientRegexes = [
			IRequest::USER_AGENT_CLIENT_ANDROID,
			IRequest::USER_AGENT_CLIENT_IOS,
			IRequest::USER_AGENT_TALK_DESKTOP,
			IRequest::USER_AGENT_TALK_IOS,
			IRequest::USER_AGENT_OUTLOOK_ADDON,
			IRequest::USER_AGENT_THUNDERBIRD_ADDON,
			// omit Desktop Client here, we block it later in BlockLegacyClientPlugin via WebDav Blocking
			// the error message in the client gets shown only after login flow
		];

		foreach ($blockedClientRegexes as $regex) {
			if (preg_match($regex, $userAgent)) {
				$app = $this->config->getSystemValue('iserv_app_name', 'Wolke');
				throw new Forbidden("Access to $app via an external client is forbidden. Please visit $app in a browser or the IServ app.");
			}
		}
	}
}
