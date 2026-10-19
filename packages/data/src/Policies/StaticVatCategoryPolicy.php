<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticVatCategory;

class StaticVatCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticVatCategory');
    }

    public function view(AuthUser $authUser, StaticVatCategory $staticVatCategory): bool
    {
        return $authUser->can('View:StaticVatCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticVatCategory');
    }

    public function update(AuthUser $authUser, StaticVatCategory $staticVatCategory): bool
    {
        return $authUser->can('Update:StaticVatCategory');
    }

    public function delete(AuthUser $authUser, StaticVatCategory $staticVatCategory): bool
    {
        return $authUser->can('Delete:StaticVatCategory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticVatCategory');
    }

    public function restore(AuthUser $authUser, StaticVatCategory $staticVatCategory): bool
    {
        return $authUser->can('Restore:StaticVatCategory');
    }

    public function forceDelete(AuthUser $authUser, StaticVatCategory $staticVatCategory): bool
    {
        return $authUser->can('ForceDelete:StaticVatCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticVatCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticVatCategory');
    }

    public function replicate(AuthUser $authUser, StaticVatCategory $staticVatCategory): bool
    {
        return $authUser->can('Replicate:StaticVatCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticVatCategory');
    }
}
