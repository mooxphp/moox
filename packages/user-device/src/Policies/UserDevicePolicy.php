<?php

declare(strict_types=1);

namespace Moox\UserDevice\Policies;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class UserDevicePolicy
{
    public function viewAny(Authorizable $user): bool
    {
        return true;
    }

    public function view(Authorizable $user, Model $record): bool
    {
        if ($this->isShieldAdmin($user)) {
            return true;
        }

        return $this->ownsRecord($user, $record);
    }

    public function create(Authorizable $user): bool
    {
        return false;
    }

    public function update(Authorizable $user, Model $record): bool
    {
        if ($this->isShieldAdmin($user)) {
            return true;
        }

        return $this->ownsRecord($user, $record);
    }

    public function delete(Authorizable $user, Model $record): bool
    {
        return $this->isShieldAdmin($user);
    }

    protected function permissionSystemAvailable(): bool
    {
        if (! class_exists(PermissionRegistrar::class)) {
            return false;
        }

        return Schema::hasTable('permissions') && Schema::hasTable('roles');
    }

    protected function ownsRecord(Authorizable $user, Model $record): bool
    {
        if (! method_exists($user, 'getAuthIdentifier')) {
            return false;
        }

        /** @var mixed $userId */
        $userId = $user->getAuthIdentifier();

        return ((string) $record->getAttribute('user_id') === (string) $userId)
            && ((string) $record->getAttribute('user_type') === $user::class);
    }

    protected function isShieldAdmin(Authorizable $user): bool
    {
        if (! $this->permissionSystemAvailable()) {
            return false;
        }

        if (! method_exists($user, 'hasRole')) {
            return false;
        }

        $roleName = (string) config('filament-shield.super_admin.name', 'super_admin');

        /** @phpstan-ignore-next-line */
        return (bool) $user->hasRole($roleName);
    }
}
