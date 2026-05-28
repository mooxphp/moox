<?php

declare(strict_types=1);

namespace Moox\User\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Schema;
use Moox\User\Models\User;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(Authorizable $authUser): bool
    {
        return $this->allowsByPermission($authUser, 'ViewAny:User');
    }

    public function viewAll(Authorizable $authUser): bool
    {
        return $this->allowsByPermission($authUser, 'ViewAll:User');
    }

    public function viewTabs(Authorizable $authUser): bool
    {
        return $this->allowsByPermission($authUser, 'ViewTabs:User');
    }

    public function view(Authorizable $authUser, User $user): bool
    {
        return $this->allowsByPermission($authUser, 'View:User');
    }

    public function create(Authorizable $authUser): bool
    {
        return $this->allowsByPermission($authUser, 'Create:User');
    }

    public function update(Authorizable $authUser, User $user): bool
    {
        return $this->allowsByPermission($authUser, 'Update:User');
    }

    public function delete(Authorizable $authUser, User $user): bool
    {
        return $this->allowsByPermission($authUser, 'Delete:User');
    }

    public function deleteAny(Authorizable $authUser): bool
    {
        return $this->allowsByPermission($authUser, 'DeleteAny:User');
    }

    public function restore(Authorizable $authUser, User $user): bool
    {
        return $this->allowsByPermission($authUser, 'Restore:User');
    }

    public function restoreAny(Authorizable $authUser): bool
    {
        return $this->allowsByPermission($authUser, 'RestoreAny:User');
    }

    public function forceDelete(Authorizable $authUser, User $user): bool
    {
        return $this->allowsByPermission($authUser, 'ForceDelete:User');
    }

    public function forceDeleteAny(Authorizable $authUser): bool
    {
        return $this->allowsByPermission($authUser, 'ForceDeleteAny:User');
    }

    public function replicate(Authorizable $authUser, User $user): bool
    {
        return $this->allowsByPermission($authUser, 'Replicate:User');
    }

    public function reorder(Authorizable $authUser): bool
    {
        return $this->allowsByPermission($authUser, 'Reorder:User');
    }

    protected function allowsByPermission(Authorizable $authUser, string $permissionName): bool
    {
        if (! $this->permissionSystemAvailable()) {
            return true;
        }

        if (! $this->permissionExists($permissionName)) {
            return false;
        }

        return $authUser->can($permissionName);
    }

    protected function permissionSystemAvailable(): bool
    {
        return class_exists(\Spatie\Permission\Models\Permission::class) && Schema::hasTable('permissions');
    }

    protected function permissionExists(string $permissionName): bool
    {
        if (! class_exists(\Spatie\Permission\Models\Permission::class)) {
            return false;
        }

        $permissionModel = \Spatie\Permission\Models\Permission::class;

        return $permissionModel::query()
            ->where('name', $permissionName)
            ->exists();
    }
}
