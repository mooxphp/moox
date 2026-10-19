<?php

declare(strict_types=1);

namespace Moox\Staff\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Staff\Models\Staff;

class StaffPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Staff');
    }

    public function view(AuthUser $authUser, Staff $staff): bool
    {
        return $authUser->can('View:Staff');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Staff');
    }

    public function update(AuthUser $authUser, Staff $staff): bool
    {
        return $authUser->can('Update:Staff');
    }

    public function delete(AuthUser $authUser, Staff $staff): bool
    {
        return $authUser->can('Delete:Staff');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Staff');
    }

    public function restore(AuthUser $authUser, Staff $staff): bool
    {
        return $authUser->can('Restore:Staff');
    }

    public function forceDelete(AuthUser $authUser, Staff $staff): bool
    {
        return $authUser->can('ForceDelete:Staff');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Staff');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Staff');
    }

    public function replicate(AuthUser $authUser, Staff $staff): bool
    {
        return $authUser->can('Replicate:Staff');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Staff');
    }
}
