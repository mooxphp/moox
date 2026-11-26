<?php

declare(strict_types=1);

namespace Moox\ProductCategory\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\ProductCategory\Models\ProductCategory;

class ProductCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProductCategory');
    }

    public function view(AuthUser $authUser, ProductCategory $productCategory): bool
    {
        return $authUser->can('View:ProductCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductCategory');
    }

    public function update(AuthUser $authUser, ProductCategory $productCategory): bool
    {
        return $authUser->can('Update:ProductCategory');
    }

    public function delete(AuthUser $authUser, ProductCategory $productCategory): bool
    {
        return $authUser->can('Delete:ProductCategory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProductCategory');
    }

    public function restore(AuthUser $authUser, ProductCategory $productCategory): bool
    {
        return $authUser->can('Restore:ProductCategory');
    }

    public function forceDelete(AuthUser $authUser, ProductCategory $productCategory): bool
    {
        return $authUser->can('ForceDelete:ProductCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProductCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProductCategory');
    }

    public function replicate(AuthUser $authUser, ProductCategory $productCategory): bool
    {
        return $authUser->can('Replicate:ProductCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProductCategory');
    }
}
