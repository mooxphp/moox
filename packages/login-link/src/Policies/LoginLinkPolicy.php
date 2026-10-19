<?php

declare(strict_types=1);

namespace Moox\LoginLink\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\LoginLink\Models\LoginLink;

class LoginLinkPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LoginLink');
    }

    public function view(AuthUser $authUser, LoginLink $loginLink): bool
    {
        return $authUser->can('View:LoginLink');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LoginLink');
    }

    public function update(AuthUser $authUser, LoginLink $loginLink): bool
    {
        return $authUser->can('Update:LoginLink');
    }

    public function delete(AuthUser $authUser, LoginLink $loginLink): bool
    {
        return $authUser->can('Delete:LoginLink');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:LoginLink');
    }

    public function restore(AuthUser $authUser, LoginLink $loginLink): bool
    {
        return $authUser->can('Restore:LoginLink');
    }

    public function forceDelete(AuthUser $authUser, LoginLink $loginLink): bool
    {
        return $authUser->can('ForceDelete:LoginLink');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LoginLink');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LoginLink');
    }

    public function replicate(AuthUser $authUser, LoginLink $loginLink): bool
    {
        return $authUser->can('Replicate:LoginLink');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LoginLink');
    }
}
