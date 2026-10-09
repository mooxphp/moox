<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticAllowanceReason;

class StaticAllowanceReasonPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticAllowanceReason');
    }

    public function view(AuthUser $authUser, StaticAllowanceReason $staticAllowanceReason): bool
    {
        return $authUser->can('View:StaticAllowanceReason');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticAllowanceReason');
    }

    public function update(AuthUser $authUser, StaticAllowanceReason $staticAllowanceReason): bool
    {
        return $authUser->can('Update:StaticAllowanceReason');
    }

    public function delete(AuthUser $authUser, StaticAllowanceReason $staticAllowanceReason): bool
    {
        return $authUser->can('Delete:StaticAllowanceReason');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticAllowanceReason');
    }

    public function restore(AuthUser $authUser, StaticAllowanceReason $staticAllowanceReason): bool
    {
        return $authUser->can('Restore:StaticAllowanceReason');
    }

    public function forceDelete(AuthUser $authUser, StaticAllowanceReason $staticAllowanceReason): bool
    {
        return $authUser->can('ForceDelete:StaticAllowanceReason');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticAllowanceReason');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticAllowanceReason');
    }

    public function replicate(AuthUser $authUser, StaticAllowanceReason $staticAllowanceReason): bool
    {
        return $authUser->can('Replicate:StaticAllowanceReason');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticAllowanceReason');
    }
}
