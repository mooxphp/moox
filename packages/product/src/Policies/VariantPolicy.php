<?php

declare(strict_types=1);

namespace Moox\Product\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Product\Models\Variant;

class VariantPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Variant');
    }

    public function view(AuthUser $authUser, Variant $variant): bool
    {
        return $authUser->can('View:Variant');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Variant');
    }

    public function update(AuthUser $authUser, Variant $variant): bool
    {
        return $authUser->can('Update:Variant');
    }

    public function delete(AuthUser $authUser, Variant $variant): bool
    {
        return $authUser->can('Delete:Variant');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Variant');
    }

    public function restore(AuthUser $authUser, Variant $variant): bool
    {
        return $authUser->can('Restore:Variant');
    }

    public function forceDelete(AuthUser $authUser, Variant $variant): bool
    {
        return $authUser->can('ForceDelete:Variant');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Variant');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Variant');
    }

    public function replicate(AuthUser $authUser, Variant $variant): bool
    {
        return $authUser->can('Replicate:Variant');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Variant');
    }
}
