<?php

declare(strict_types=1);

namespace Moox\Communication\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CommunicationRulePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CommunicationRule');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:CommunicationRule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CommunicationRule');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:CommunicationRule');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:CommunicationRule');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CommunicationRule');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:CommunicationRule');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:CommunicationRule');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CommunicationRule');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CommunicationRule');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:CommunicationRule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CommunicationRule');
    }
}
