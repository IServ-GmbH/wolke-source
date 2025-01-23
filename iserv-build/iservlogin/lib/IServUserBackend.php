<?php

declare(strict_types=1);

namespace OCA\IServLogin;

use OC\User\Backend;
use OCP\Http\Client\IClientService;
use OCP\IUserBackend;
use OCP\UserInterface;
use Psr\Log\LoggerInterface;

class IServUserBackend extends Backend implements UserInterface, IUserBackend
{
    private LoggerInterface $logger;

    private IClientService $clientService;

    public function __construct(
        private readonly string $serverDomain
    ) {
        $this->logger = \OC::$server->get(LoggerInterface::class);
        $this->clientService = \OC::$server->get(IClientService::class);
    }

    public function getBackendName(): string
    {
        return 'iserv_login';
    }

    public function checkPassword(string $loginName, string $password): string|bool
    {
        if ($loginName !== 'iserv_oauth-access-token') {
            return false;
        }

        // maybe we need to cache the introspection result here
        $introspector = new TokenIntrospection($this->clientService->newClient(), $this->serverDomain);
        try {
            $token = $introspector->userInformationFromToken($password);
            $requiredScopesGranted =
                in_array('iserv:web-ui', $token->scopes, true)
                || in_array('iserv:cloudfiles', $token->scopes, true);

            if ($token->active && $requiredScopesGranted) {
                return $token->uuid;
            }
        } catch (IntrospectionFailed $e) {
            $this->logger->error(
                'introspection failed',
                ['exception' => $e]
            );
        }

        return false;
    }
}
