<?php

declare(strict_types=1);

namespace Moox\Builder\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Builder\Models\FieldGroup;

class FieldGroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FieldGroup');
    }

    public function view(AuthUser $authUser, FieldGroup $fieldGroup): bool
    {
        return $authUser->can('View:FieldGroup');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FieldGroup');
    }

    public function update(AuthUser $authUser, FieldGroup $fieldGroup): bool
    {
        return $authUser->can('Update:FieldGroup');
    }

    public function delete(AuthUser $authUser, FieldGroup $fieldGroup): bool
    {
        return $authUser->can('Delete:FieldGroup');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FieldGroup');
    }

    public function restore(AuthUser $authUser, FieldGroup $fieldGroup): bool
    {
        return $authUser->can('Restore:FieldGroup');
    }

    public function forceDelete(AuthUser $authUser, FieldGroup $fieldGroup): bool
    {
        return $authUser->can('ForceDelete:FieldGroup');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FieldGroup');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FieldGroup');
    }

    public function replicate(AuthUser $authUser, FieldGroup $fieldGroup): bool
    {
        return $authUser->can('Replicate:FieldGroup');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FieldGroup');
    }
}
