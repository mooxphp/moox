<?php

declare(strict_types=1);

namespace Moox\Product\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Product\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Product');
    }

    public function view(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('View:Product');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Product');
    }

    public function update(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('Update:Product');
    }

    public function delete(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('Delete:Product');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Product');
    }

    public function restore(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('Restore:Product');
    }

    public function forceDelete(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('ForceDelete:Product');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Product');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Product');
    }

    public function replicate(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can('Replicate:Product');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Product');
    }

}