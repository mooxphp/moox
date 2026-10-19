<?php

declare(strict_types=1);

namespace Moox\Connect\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Connect\Models\ApiConnection;

class ApiConnectionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ApiConnection');
    }

    public function view(AuthUser $authUser, ApiConnection $apiConnection): bool
    {
        return $authUser->can('View:ApiConnection');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ApiConnection');
    }

    public function update(AuthUser $authUser, ApiConnection $apiConnection): bool
    {
        return $authUser->can('Update:ApiConnection');
    }

    public function delete(AuthUser $authUser, ApiConnection $apiConnection): bool
    {
        return $authUser->can('Delete:ApiConnection');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ApiConnection');
    }

    public function restore(AuthUser $authUser, ApiConnection $apiConnection): bool
    {
        return $authUser->can('Restore:ApiConnection');
    }

    public function forceDelete(AuthUser $authUser, ApiConnection $apiConnection): bool
    {
        return $authUser->can('ForceDelete:ApiConnection');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ApiConnection');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ApiConnection');
    }

    public function replicate(AuthUser $authUser, ApiConnection $apiConnection): bool
    {
        return $authUser->can('Replicate:ApiConnection');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ApiConnection');
    }
}
