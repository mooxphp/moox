<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticIncoterm;

class StaticIncotermPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticIncoterm');
    }

    public function view(AuthUser $authUser, StaticIncoterm $staticIncoterm): bool
    {
        return $authUser->can('View:StaticIncoterm');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticIncoterm');
    }

    public function update(AuthUser $authUser, StaticIncoterm $staticIncoterm): bool
    {
        return $authUser->can('Update:StaticIncoterm');
    }

    public function delete(AuthUser $authUser, StaticIncoterm $staticIncoterm): bool
    {
        return $authUser->can('Delete:StaticIncoterm');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticIncoterm');
    }

    public function restore(AuthUser $authUser, StaticIncoterm $staticIncoterm): bool
    {
        return $authUser->can('Restore:StaticIncoterm');
    }

    public function forceDelete(AuthUser $authUser, StaticIncoterm $staticIncoterm): bool
    {
        return $authUser->can('ForceDelete:StaticIncoterm');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticIncoterm');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticIncoterm');
    }

    public function replicate(AuthUser $authUser, StaticIncoterm $staticIncoterm): bool
    {
        return $authUser->can('Replicate:StaticIncoterm');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticIncoterm');
    }
}
