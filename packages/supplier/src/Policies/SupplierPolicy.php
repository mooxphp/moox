<?php

declare(strict_types=1);

namespace Moox\Supplier\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Supplier\Models\Supplier;

class SupplierPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Supplier');
    }

    public function view(AuthUser $authUser, Supplier $supplier): bool
    {
        return $authUser->can('View:Supplier');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Supplier');
    }

    public function update(AuthUser $authUser, Supplier $supplier): bool
    {
        return $authUser->can('Update:Supplier');
    }

    public function delete(AuthUser $authUser, Supplier $supplier): bool
    {
        return $authUser->can('Delete:Supplier');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Supplier');
    }

    public function restore(AuthUser $authUser, Supplier $supplier): bool
    {
        return $authUser->can('Restore:Supplier');
    }

    public function forceDelete(AuthUser $authUser, Supplier $supplier): bool
    {
        return $authUser->can('ForceDelete:Supplier');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Supplier');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Supplier');
    }

    public function replicate(AuthUser $authUser, Supplier $supplier): bool
    {
        return $authUser->can('Replicate:Supplier');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Supplier');
    }
}
