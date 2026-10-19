<?php

declare(strict_types=1);

namespace Moox\LoginLink\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\LoginLink\Models\LoginLinkProcess;

class LoginLinkProcessPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LoginLinkProcess');
    }

    public function view(AuthUser $authUser, LoginLinkProcess $loginLinkProcess): bool
    {
        return $authUser->can('View:LoginLinkProcess');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LoginLinkProcess');
    }

    public function update(AuthUser $authUser, LoginLinkProcess $loginLinkProcess): bool
    {
        return $authUser->can('Update:LoginLinkProcess');
    }

    public function delete(AuthUser $authUser, LoginLinkProcess $loginLinkProcess): bool
    {
        return $authUser->can('Delete:LoginLinkProcess');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:LoginLinkProcess');
    }

    public function restore(AuthUser $authUser, LoginLinkProcess $loginLinkProcess): bool
    {
        return $authUser->can('Restore:LoginLinkProcess');
    }

    public function forceDelete(AuthUser $authUser, LoginLinkProcess $loginLinkProcess): bool
    {
        return $authUser->can('ForceDelete:LoginLinkProcess');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LoginLinkProcess');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LoginLinkProcess');
    }

    public function replicate(AuthUser $authUser, LoginLinkProcess $loginLinkProcess): bool
    {
        return $authUser->can('Replicate:LoginLinkProcess');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LoginLinkProcess');
    }
}
