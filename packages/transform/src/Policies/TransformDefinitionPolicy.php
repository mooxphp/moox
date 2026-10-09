<?php

declare(strict_types=1);

namespace Moox\Transform\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Transform\Models\TransformDefinition;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransformDefinitionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TransformDefinition');
    }

    public function view(AuthUser $authUser, TransformDefinition $transformDefinition): bool
    {
        return $authUser->can('View:TransformDefinition');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TransformDefinition');
    }

    public function update(AuthUser $authUser, TransformDefinition $transformDefinition): bool
    {
        return $authUser->can('Update:TransformDefinition');
    }

    public function delete(AuthUser $authUser, TransformDefinition $transformDefinition): bool
    {
        return $authUser->can('Delete:TransformDefinition');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TransformDefinition');
    }

    public function restore(AuthUser $authUser, TransformDefinition $transformDefinition): bool
    {
        return $authUser->can('Restore:TransformDefinition');
    }

    public function forceDelete(AuthUser $authUser, TransformDefinition $transformDefinition): bool
    {
        return $authUser->can('ForceDelete:TransformDefinition');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TransformDefinition');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TransformDefinition');
    }

    public function replicate(AuthUser $authUser, TransformDefinition $transformDefinition): bool
    {
        return $authUser->can('Replicate:TransformDefinition');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TransformDefinition');
    }

}