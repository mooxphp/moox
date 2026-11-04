<?php

declare(strict_types=1);

namespace Moox\Communication\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Communication\Models\Communication;

class CommunicationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Communication');
    }

    public function view(AuthUser $authUser, Communication $communication): bool
    {
        return $authUser->can('View:Communication');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Communication');
    }

    public function update(AuthUser $authUser, Communication $communication): bool
    {
        return $authUser->can('Update:Communication');
    }

    public function delete(AuthUser $authUser, Communication $communication): bool
    {
        return $authUser->can('Delete:Communication');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Communication');
    }

    public function restore(AuthUser $authUser, Communication $communication): bool
    {
        return $authUser->can('Restore:Communication');
    }

    public function forceDelete(AuthUser $authUser, Communication $communication): bool
    {
        return $authUser->can('ForceDelete:Communication');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Communication');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Communication');
    }

    public function replicate(AuthUser $authUser, Communication $communication): bool
    {
        return $authUser->can('Replicate:Communication');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Communication');
    }
}
