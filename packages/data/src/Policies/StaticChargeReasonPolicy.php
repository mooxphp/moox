<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticChargeReason;

class StaticChargeReasonPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticChargeReason');
    }

    public function view(AuthUser $authUser, StaticChargeReason $staticChargeReason): bool
    {
        return $authUser->can('View:StaticChargeReason');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticChargeReason');
    }

    public function update(AuthUser $authUser, StaticChargeReason $staticChargeReason): bool
    {
        return $authUser->can('Update:StaticChargeReason');
    }

    public function delete(AuthUser $authUser, StaticChargeReason $staticChargeReason): bool
    {
        return $authUser->can('Delete:StaticChargeReason');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticChargeReason');
    }

    public function restore(AuthUser $authUser, StaticChargeReason $staticChargeReason): bool
    {
        return $authUser->can('Restore:StaticChargeReason');
    }

    public function forceDelete(AuthUser $authUser, StaticChargeReason $staticChargeReason): bool
    {
        return $authUser->can('ForceDelete:StaticChargeReason');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticChargeReason');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticChargeReason');
    }

    public function replicate(AuthUser $authUser, StaticChargeReason $staticChargeReason): bool
    {
        return $authUser->can('Replicate:StaticChargeReason');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticChargeReason');
    }
}
