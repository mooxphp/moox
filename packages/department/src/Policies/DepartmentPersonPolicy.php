<?php

declare(strict_types=1);

namespace Moox\Department\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Department\Models\DepartmentPerson;

class DepartmentPersonPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DepartmentPerson');
    }

    public function view(AuthUser $authUser, DepartmentPerson $departmentPerson): bool
    {
        return $authUser->can('View:DepartmentPerson');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DepartmentPerson');
    }

    public function update(AuthUser $authUser, DepartmentPerson $departmentPerson): bool
    {
        return $authUser->can('Update:DepartmentPerson');
    }

    public function delete(AuthUser $authUser, DepartmentPerson $departmentPerson): bool
    {
        return $authUser->can('Delete:DepartmentPerson');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DepartmentPerson');
    }

    public function restore(AuthUser $authUser, DepartmentPerson $departmentPerson): bool
    {
        return $authUser->can('Restore:DepartmentPerson');
    }

    public function forceDelete(AuthUser $authUser, DepartmentPerson $departmentPerson): bool
    {
        return $authUser->can('ForceDelete:DepartmentPerson');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DepartmentPerson');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DepartmentPerson');
    }

    public function replicate(AuthUser $authUser, DepartmentPerson $departmentPerson): bool
    {
        return $authUser->can('Replicate:DepartmentPerson');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DepartmentPerson');
    }
}
