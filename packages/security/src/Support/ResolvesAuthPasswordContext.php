<?php

declare(strict_types=1);

namespace Moox\Security\Support;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Model;

trait ResolvesAuthPasswordContext
{
    protected function resolveAuthModelClass(): ?string
    {
        $guard = Filament::getAuthGuard();
        $provider = config("auth.guards.{$guard}.provider");

        if (! is_string($provider) || $provider === '') {
            return null;
        }

        $model = config("auth.providers.{$provider}.model");

        return is_string($model) && $model !== '' ? $model : null;
    }

    protected function modelUsesWordPressPassword(CanResetPassword|Model|Authenticatable $user): bool
    {
        if (! $user instanceof Model) {
            return false;
        }

        return $user->isFillable('user_pass') && ! $user->isFillable('password');
    }

    protected function panelAuthUsesWordPressPassword(): bool
    {
        $modelClass = $this->resolveAuthModelClass();

        if ($modelClass === null || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return false;
        }

        return $this->modelUsesWordPressPassword(new $modelClass);
    }
}
