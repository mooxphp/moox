<?php

declare(strict_types=1);

namespace Moox\Address\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Address\Models\Address;
use Illuminate\Auth\Access\HandlesAuthorization;

class AddressPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Address');
    }

    public function view(AuthUser $authUser, Address $address): bool
    {
        return $authUser->can('View:Address');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Address');
    }

    public function update(AuthUser $authUser, Address $address): bool
    {
        return $authUser->can('Update:Address');
    }

    public function delete(AuthUser $authUser, Address $address): bool
    {
        return $authUser->can('Delete:Address');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Address');
    }

    public function restore(AuthUser $authUser, Address $address): bool
    {
        return $authUser->can('Restore:Address');
    }

    public function forceDelete(AuthUser $authUser, Address $address): bool
    {
        return $authUser->can('ForceDelete:Address');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Address');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Address');
    }

    public function replicate(AuthUser $authUser, Address $address): bool
    {
        return $authUser->can('Replicate:Address');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Address');
    }

}