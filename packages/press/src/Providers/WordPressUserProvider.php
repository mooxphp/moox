<?php

namespace Moox\Press\Providers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Hashing\Hasher;
use Moox\Press\Services\WordPressAuthService;
use Override;

class WordPressUserProvider extends EloquentUserProvider
{
    protected WordPressAuthService $wpAuthService;

    public function __construct(Hasher $hasher, $model)
    {
        parent::__construct($hasher, $model);
        $this->wpAuthService = new WordPressAuthService;
    }

    #[Override]
    public function validateCredentials(UserContract $user, array $credentials)
    {
        $plain = $credentials['password'];

        return $this->wpAuthService->checkPassword($plain, $user->getAuthPassword());
    }
}
