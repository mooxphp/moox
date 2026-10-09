<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticUnit;

class StaticUnitPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticUnit');
    }

    public function view(AuthUser $authUser, StaticUnit $staticUnit): bool
    {
        return $authUser->can('View:StaticUnit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticUnit');
    }

    public function update(AuthUser $authUser, StaticUnit $staticUnit): bool
    {
        return $authUser->can('Update:StaticUnit');
    }

    public function delete(AuthUser $authUser, StaticUnit $staticUnit): bool
    {
        return $authUser->can('Delete:StaticUnit');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticUnit');
    }

    public function restore(AuthUser $authUser, StaticUnit $staticUnit): bool
    {
        return $authUser->can('Restore:StaticUnit');
    }

    public function forceDelete(AuthUser $authUser, StaticUnit $staticUnit): bool
    {
        return $authUser->can('ForceDelete:StaticUnit');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticUnit');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticUnit');
    }

    public function replicate(AuthUser $authUser, StaticUnit $staticUnit): bool
    {
        return $authUser->can('Replicate:StaticUnit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticUnit');
    }
}
