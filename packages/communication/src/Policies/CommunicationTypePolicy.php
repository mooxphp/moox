<?php

declare(strict_types=1);

namespace Moox\Communication\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Communication\Models\CommunicationType;

class CommunicationTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CommunicationType');
    }

    public function view(AuthUser $authUser, CommunicationType $communicationType): bool
    {
        return $authUser->can('View:CommunicationType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CommunicationType');
    }

    public function update(AuthUser $authUser, CommunicationType $communicationType): bool
    {
        return $authUser->can('Update:CommunicationType');
    }

    public function delete(AuthUser $authUser, CommunicationType $communicationType): bool
    {
        return $authUser->can('Delete:CommunicationType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CommunicationType');
    }

    public function restore(AuthUser $authUser, CommunicationType $communicationType): bool
    {
        return $authUser->can('Restore:CommunicationType');
    }

    public function forceDelete(AuthUser $authUser, CommunicationType $communicationType): bool
    {
        return $authUser->can('ForceDelete:CommunicationType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CommunicationType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CommunicationType');
    }

    public function replicate(AuthUser $authUser, CommunicationType $communicationType): bool
    {
        return $authUser->can('Replicate:CommunicationType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CommunicationType');
    }
}
