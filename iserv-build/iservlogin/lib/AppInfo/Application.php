<?php

namespace OCA\IServLogin\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap
{
    public function __construct()
    {
        parent::__construct('iserv_login');
    }

    public function boot(IBootContext $context): void
    {
        // NOOP
    }

    public function register(IRegistrationContext $context): void
    {
        // Register the composer autoloader for packages shipped by this app, if applicable
        include_once __DIR__ . '/../../vendor/autoload.php';
    }
}
