<?php

declare(strict_types=1);

namespace Moox\Connect\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Connect\Models\ApiLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApiLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ApiLog');
    }

    public function view(AuthUser $authUser, ApiLog $apiLog): bool
    {
        return $authUser->can('View:ApiLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ApiLog');
    }

    public function update(AuthUser $authUser, ApiLog $apiLog): bool
    {
        return $authUser->can('Update:ApiLog');
    }

    public function delete(AuthUser $authUser, ApiLog $apiLog): bool
    {
        return $authUser->can('Delete:ApiLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ApiLog');
    }

    public function restore(AuthUser $authUser, ApiLog $apiLog): bool
    {
        return $authUser->can('Restore:ApiLog');
    }

    public function forceDelete(AuthUser $authUser, ApiLog $apiLog): bool
    {
        return $authUser->can('ForceDelete:ApiLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ApiLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ApiLog');
    }

    public function replicate(AuthUser $authUser, ApiLog $apiLog): bool
    {
        return $authUser->can('Replicate:ApiLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ApiLog');
    }

}