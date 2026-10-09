<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\ProductGroup\Models\ProductGroup;

class ProductGroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProductGroup');
    }

    public function view(AuthUser $authUser, ProductGroup $productGroup): bool
    {
        return $authUser->can('View:ProductGroup');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductGroup');
    }

    public function update(AuthUser $authUser, ProductGroup $productGroup): bool
    {
        return $authUser->can('Update:ProductGroup');
    }

    public function delete(AuthUser $authUser, ProductGroup $productGroup): bool
    {
        return $authUser->can('Delete:ProductGroup');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProductGroup');
    }

    public function restore(AuthUser $authUser, ProductGroup $productGroup): bool
    {
        return $authUser->can('Restore:ProductGroup');
    }

    public function forceDelete(AuthUser $authUser, ProductGroup $productGroup): bool
    {
        return $authUser->can('ForceDelete:ProductGroup');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProductGroup');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProductGroup');
    }

    public function replicate(AuthUser $authUser, ProductGroup $productGroup): bool
    {
        return $authUser->can('Replicate:ProductGroup');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProductGroup');
    }
}
