<?php

namespace Moox\Press\Services;

use Moox\Security\Helper\PasswordHash;

class WordPressAuthService
{
    protected PasswordHash $hasher;

    public function __construct()
    {
        $this->hasher = new PasswordHash(8, true);
    }

    public function hashPassword(string $password): string
    {
        return $this->hasher->HashPassword($password);
    }

    public function checkPassword(string $password, $hashedPassword): bool
    {
        return $this->hasher->CheckPassword($password, $hashedPassword);
    }
}
