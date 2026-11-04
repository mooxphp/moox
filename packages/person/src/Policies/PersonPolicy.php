<?php

declare(strict_types=1);

namespace Moox\Person\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Person\Models\Person;

class PersonPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Person');
    }

    public function view(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('View:Person');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Person');
    }

    public function update(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('Update:Person');
    }

    public function delete(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('Delete:Person');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Person');
    }

    public function restore(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('Restore:Person');
    }

    public function forceDelete(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('ForceDelete:Person');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Person');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Person');
    }

    public function replicate(AuthUser $authUser, Person $person): bool
    {
        return $authUser->can('Replicate:Person');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Person');
    }
}
