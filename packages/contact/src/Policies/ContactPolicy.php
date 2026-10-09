<?php

namespace Moox\Contact\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Contact');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:Contact');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Contact');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Contact');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Contact');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Contact');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:Contact');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:Contact');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Contact');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Contact');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:Contact');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Contact');
    }

}