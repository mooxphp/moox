<?php

declare(strict_types=1);

namespace Moox\Connect\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Connect\Models\ApiEndpoint;

class ApiEndpointPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ApiEndpoint');
    }

    public function view(AuthUser $authUser, ApiEndpoint $apiEndpoint): bool
    {
        return $authUser->can('View:ApiEndpoint');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ApiEndpoint');
    }

    public function update(AuthUser $authUser, ApiEndpoint $apiEndpoint): bool
    {
        return $authUser->can('Update:ApiEndpoint');
    }

    public function delete(AuthUser $authUser, ApiEndpoint $apiEndpoint): bool
    {
        return $authUser->can('Delete:ApiEndpoint');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ApiEndpoint');
    }

    public function restore(AuthUser $authUser, ApiEndpoint $apiEndpoint): bool
    {
        return $authUser->can('Restore:ApiEndpoint');
    }

    public function forceDelete(AuthUser $authUser, ApiEndpoint $apiEndpoint): bool
    {
        return $authUser->can('ForceDelete:ApiEndpoint');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ApiEndpoint');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ApiEndpoint');
    }

    public function replicate(AuthUser $authUser, ApiEndpoint $apiEndpoint): bool
    {
        return $authUser->can('Replicate:ApiEndpoint');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ApiEndpoint');
    }
}
