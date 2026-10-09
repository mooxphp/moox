<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticVatExemptionReason;
use Illuminate\Auth\Access\HandlesAuthorization;

class StaticVatExemptionReasonPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticVatExemptionReason');
    }

    public function view(AuthUser $authUser, StaticVatExemptionReason $staticVatExemptionReason): bool
    {
        return $authUser->can('View:StaticVatExemptionReason');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticVatExemptionReason');
    }

    public function update(AuthUser $authUser, StaticVatExemptionReason $staticVatExemptionReason): bool
    {
        return $authUser->can('Update:StaticVatExemptionReason');
    }

    public function delete(AuthUser $authUser, StaticVatExemptionReason $staticVatExemptionReason): bool
    {
        return $authUser->can('Delete:StaticVatExemptionReason');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticVatExemptionReason');
    }

    public function restore(AuthUser $authUser, StaticVatExemptionReason $staticVatExemptionReason): bool
    {
        return $authUser->can('Restore:StaticVatExemptionReason');
    }

    public function forceDelete(AuthUser $authUser, StaticVatExemptionReason $staticVatExemptionReason): bool
    {
        return $authUser->can('ForceDelete:StaticVatExemptionReason');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticVatExemptionReason');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticVatExemptionReason');
    }

    public function replicate(AuthUser $authUser, StaticVatExemptionReason $staticVatExemptionReason): bool
    {
        return $authUser->can('Replicate:StaticVatExemptionReason');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticVatExemptionReason');
    }

}