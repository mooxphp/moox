<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticCurrency;

class StaticCurrencyPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticCurrency');
    }

    public function view(AuthUser $authUser, StaticCurrency $staticCurrency): bool
    {
        return $authUser->can('View:StaticCurrency');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticCurrency');
    }

    public function update(AuthUser $authUser, StaticCurrency $staticCurrency): bool
    {
        return $authUser->can('Update:StaticCurrency');
    }

    public function delete(AuthUser $authUser, StaticCurrency $staticCurrency): bool
    {
        return $authUser->can('Delete:StaticCurrency');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticCurrency');
    }

    public function restore(AuthUser $authUser, StaticCurrency $staticCurrency): bool
    {
        return $authUser->can('Restore:StaticCurrency');
    }

    public function forceDelete(AuthUser $authUser, StaticCurrency $staticCurrency): bool
    {
        return $authUser->can('ForceDelete:StaticCurrency');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticCurrency');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticCurrency');
    }

    public function replicate(AuthUser $authUser, StaticCurrency $staticCurrency): bool
    {
        return $authUser->can('Replicate:StaticCurrency');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticCurrency');
    }
}
