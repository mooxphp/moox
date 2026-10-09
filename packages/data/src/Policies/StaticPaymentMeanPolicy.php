<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticPaymentMean;
use Illuminate\Auth\Access\HandlesAuthorization;

class StaticPaymentMeanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticPaymentMean');
    }

    public function view(AuthUser $authUser, StaticPaymentMean $staticPaymentMean): bool
    {
        return $authUser->can('View:StaticPaymentMean');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticPaymentMean');
    }

    public function update(AuthUser $authUser, StaticPaymentMean $staticPaymentMean): bool
    {
        return $authUser->can('Update:StaticPaymentMean');
    }

    public function delete(AuthUser $authUser, StaticPaymentMean $staticPaymentMean): bool
    {
        return $authUser->can('Delete:StaticPaymentMean');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticPaymentMean');
    }

    public function restore(AuthUser $authUser, StaticPaymentMean $staticPaymentMean): bool
    {
        return $authUser->can('Restore:StaticPaymentMean');
    }

    public function forceDelete(AuthUser $authUser, StaticPaymentMean $staticPaymentMean): bool
    {
        return $authUser->can('ForceDelete:StaticPaymentMean');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticPaymentMean');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticPaymentMean');
    }

    public function replicate(AuthUser $authUser, StaticPaymentMean $staticPaymentMean): bool
    {
        return $authUser->can('Replicate:StaticPaymentMean');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticPaymentMean');
    }

}