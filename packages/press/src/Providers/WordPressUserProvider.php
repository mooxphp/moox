<?php

namespace Moox\Press\Providers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Moox\Security\Services\WordPressAuthService;

class WordPressUserProvider extends EloquentUserProvider
{
    protected $wpAuthService;

    public function __construct($hasher, $model)
    {
        parent::__construct($hasher, $model);
        $this->wpAuthService = new WordPressAuthService;
    }

    public function validateCredentials(UserContract $user, array $credentials)
    {
        $plain = $credentials['password'];

        return $this->wpAuthService->checkPassword($plain, $user->getAuthPassword());
    }
}
