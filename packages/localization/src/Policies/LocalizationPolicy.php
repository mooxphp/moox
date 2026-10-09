<?php

declare(strict_types=1);

namespace Moox\Localization\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Localization\Models\Localization;

class LocalizationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Localization');
    }

    public function view(AuthUser $authUser, Localization $localization): bool
    {
        return $authUser->can('View:Localization');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Localization');
    }

    public function update(AuthUser $authUser, Localization $localization): bool
    {
        return $authUser->can('Update:Localization');
    }

    public function delete(AuthUser $authUser, Localization $localization): bool
    {
        return $authUser->can('Delete:Localization');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Localization');
    }

    public function restore(AuthUser $authUser, Localization $localization): bool
    {
        return $authUser->can('Restore:Localization');
    }

    public function forceDelete(AuthUser $authUser, Localization $localization): bool
    {
        return $authUser->can('ForceDelete:Localization');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Localization');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Localization');
    }

    public function replicate(AuthUser $authUser, Localization $localization): bool
    {
        return $authUser->can('Replicate:Localization');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Localization');
    }
}
