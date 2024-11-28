<?php

declare(strict_types=1);

namespace OCA\IServLogin;

class UserInformation
{
    public function __construct(
        public readonly bool $active,
        public readonly string $uuid,
        /** @var list<string> */
        public readonly array $scopes
    ) {
    }
}
