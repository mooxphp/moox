<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticTimezone;

class StaticTimezonePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticTimezone');
    }

    public function view(AuthUser $authUser, StaticTimezone $staticTimezone): bool
    {
        return $authUser->can('View:StaticTimezone');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticTimezone');
    }

    public function update(AuthUser $authUser, StaticTimezone $staticTimezone): bool
    {
        return $authUser->can('Update:StaticTimezone');
    }

    public function delete(AuthUser $authUser, StaticTimezone $staticTimezone): bool
    {
        return $authUser->can('Delete:StaticTimezone');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticTimezone');
    }

    public function restore(AuthUser $authUser, StaticTimezone $staticTimezone): bool
    {
        return $authUser->can('Restore:StaticTimezone');
    }

    public function forceDelete(AuthUser $authUser, StaticTimezone $staticTimezone): bool
    {
        return $authUser->can('ForceDelete:StaticTimezone');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticTimezone');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticTimezone');
    }

    public function replicate(AuthUser $authUser, StaticTimezone $staticTimezone): bool
    {
        return $authUser->can('Replicate:StaticTimezone');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticTimezone');
    }
}
