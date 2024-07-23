<?php

namespace Moox\Security\Services;

use Moox\Security\Helper\PasswordHash;

class WordPressAuthService
{
    protected $hasher;

    public function __construct()
    {
        $this->hasher = new PasswordHash(8, true);
    }

    public function hashPassword($password)
    {
        return $this->hasher->HashPassword($password);
    }

    public function checkPassword($password, $hashedPassword)
    {
        return $this->hasher->CheckPassword($password, $hashedPassword);
    }
}
