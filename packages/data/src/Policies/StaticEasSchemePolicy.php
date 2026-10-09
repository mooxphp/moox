<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticEasScheme;

class StaticEasSchemePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticEasScheme');
    }

    public function view(AuthUser $authUser, StaticEasScheme $staticEasScheme): bool
    {
        return $authUser->can('View:StaticEasScheme');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticEasScheme');
    }

    public function update(AuthUser $authUser, StaticEasScheme $staticEasScheme): bool
    {
        return $authUser->can('Update:StaticEasScheme');
    }

    public function delete(AuthUser $authUser, StaticEasScheme $staticEasScheme): bool
    {
        return $authUser->can('Delete:StaticEasScheme');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticEasScheme');
    }

    public function restore(AuthUser $authUser, StaticEasScheme $staticEasScheme): bool
    {
        return $authUser->can('Restore:StaticEasScheme');
    }

    public function forceDelete(AuthUser $authUser, StaticEasScheme $staticEasScheme): bool
    {
        return $authUser->can('ForceDelete:StaticEasScheme');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticEasScheme');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticEasScheme');
    }

    public function replicate(AuthUser $authUser, StaticEasScheme $staticEasScheme): bool
    {
        return $authUser->can('Replicate:StaticEasScheme');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticEasScheme');
    }
}
