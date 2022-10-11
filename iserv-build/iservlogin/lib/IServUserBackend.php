<?php

declare(strict_types=1);

namespace OCA\IServLogin;

use IServ\Library\SessAuth\Secret;
use IServ\Library\SessAuth\SessAuthException;
use IServ\Library\SessAuth\SystemSessAuth;
use OC\User\Backend;
use OCP\IUserBackend;
use OCP\UserInterface;
use Psr\Log\LoggerInterface;

class IServUserBackend implements UserInterface, IUserBackend
{
    private LoggerInterface $logger;
    private SystemSessAuth $sessAuth;

    public function __construct()
    {
        $this->logger = \OC::$server->get(LoggerInterface::class);
        $this->sessAuth = new SystemSessAuth('unix:///sessauthd/socket');
    }

    public function getBackendName(): string
    {
        return 'iserv_login';
    }

    public function log(mixed ...$values)
    {
        $this->logger->error('IServUserBackend: ' . json_encode($values, JSON_THROW_ON_ERROR));
    }

    /**
     * @inheritDoc
     */
    public function implementsActions($actions): bool
    {
        return (bool)($actions & (Backend::CHECK_PASSWORD));
    }

    public function checkPassword(string $loginName, string $password): string|bool
    {
        $secret = new Secret();
        $secret->secret = $password;

        try {
            return $this->sessAuth->execute($loginName, $secret, 'cloudfiles')->successful() ? $loginName : false;
        } catch (SessAuthException $e) {
            $this->log($e->getMessage());

            return false;
        }
    }

    public function deleteUser($uid)
    {
        // NOOP
    }

    public function getUsers($search = '', $limit = null, $offset = null)
    {
        // NOOP
    }

    public function userExists($uid)
    {
        // NOOP
    }

    public function getDisplayName($uid)
    {
        // NOOP
    }

    public function getDisplayNames($search = '', $limit = null, $offset = null)
    {
        // NOOP
    }

    public function hasUserListings()
    {
        // NOOP
    }
}
