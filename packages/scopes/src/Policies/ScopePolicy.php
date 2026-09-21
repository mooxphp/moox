<?php

declare(strict_types=1);

namespace Moox\Scopes\Policies;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Scopes control record isolation and navigation.
 *
 * Auth rules:
 * - No Spatie/Shield tables → panel login is enough
 * - User has no roles assigned yet → allow (roles not set up)
 * - User has roles → only Filament Shield super_admin
 */
class ScopePolicy
{
    public function viewAny(Authorizable $user): bool
    {
        return $this->isAuthorized($user);
    }

    public function view(Authorizable $user, Model $record): bool
    {
        return $this->isAuthorized($user);
    }

    public function create(Authorizable $user): bool
    {
        return $this->isAuthorized($user);
    }

    public function update(Authorizable $user, Model $record): bool
    {
        return $this->isAuthorized($user);
    }

    public function delete(Authorizable $user, Model $record): bool
    {
        return $this->isAuthorized($user);
    }

    public function restore(Authorizable $user, Model $record): bool
    {
        return $this->isAuthorized($user);
    }

    public function forceDelete(Authorizable $user, Model $record): bool
    {
        return $this->isAuthorized($user);
    }

    protected function isAuthorized(Authorizable $user): bool
    {
        if (! $this->permissionSystemAvailable()) {
            return true;
        }

        if (! method_exists($user, 'hasRole')) {
            return true;
        }

        // Roles tables exist, but this user has none → do not lock the UI out.
        if ($this->userHasNoRoles($user)) {
            return true;
        }

        return $this->isShieldAdmin($user);
    }

    protected function permissionSystemAvailable(): bool
    {
        if (! class_exists(PermissionRegistrar::class)) {
            return false;
        }

        return Schema::hasTable('permissions') && Schema::hasTable('roles');
    }

    protected function userHasNoRoles(Authorizable $user): bool
    {
        if (method_exists($user, 'getRoleNames')) {
            /** @var iterable<int, string>|mixed $names */
            $names = $user->getRoleNames();

            return collect($names)->isEmpty();
        }

        return true;
    }

    protected function isShieldAdmin(Authorizable $user): bool
    {
        $roleName = (string) config('filament-shield.super_admin.name', 'super_admin');

        return (bool) $user->hasRole($roleName);
    }
}
