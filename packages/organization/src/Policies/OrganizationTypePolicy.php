<?php

declare(strict_types=1);

namespace Moox\Organization\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Organization\Models\OrganizationType;

class OrganizationTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OrganizationType');
    }

    public function view(AuthUser $authUser, OrganizationType $organizationType): bool
    {
        return $authUser->can('View:OrganizationType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OrganizationType');
    }

    public function update(AuthUser $authUser, OrganizationType $organizationType): bool
    {
        return $authUser->can('Update:OrganizationType');
    }

    public function delete(AuthUser $authUser, OrganizationType $organizationType): bool
    {
        return $authUser->can('Delete:OrganizationType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:OrganizationType');
    }

    public function restore(AuthUser $authUser, OrganizationType $organizationType): bool
    {
        return $authUser->can('Restore:OrganizationType');
    }

    public function forceDelete(AuthUser $authUser, OrganizationType $organizationType): bool
    {
        return $authUser->can('ForceDelete:OrganizationType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OrganizationType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OrganizationType');
    }

    public function replicate(AuthUser $authUser, OrganizationType $organizationType): bool
    {
        return $authUser->can('Replicate:OrganizationType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OrganizationType');
    }
}
