<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticLocale;

class StaticLocalePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticLocale');
    }

    public function view(AuthUser $authUser, StaticLocale $staticLocale): bool
    {
        return $authUser->can('View:StaticLocale');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticLocale');
    }

    public function update(AuthUser $authUser, StaticLocale $staticLocale): bool
    {
        return $authUser->can('Update:StaticLocale');
    }

    public function delete(AuthUser $authUser, StaticLocale $staticLocale): bool
    {
        return $authUser->can('Delete:StaticLocale');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticLocale');
    }

    public function restore(AuthUser $authUser, StaticLocale $staticLocale): bool
    {
        return $authUser->can('Restore:StaticLocale');
    }

    public function forceDelete(AuthUser $authUser, StaticLocale $staticLocale): bool
    {
        return $authUser->can('ForceDelete:StaticLocale');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticLocale');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticLocale');
    }

    public function replicate(AuthUser $authUser, StaticLocale $staticLocale): bool
    {
        return $authUser->can('Replicate:StaticLocale');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticLocale');
    }
}
