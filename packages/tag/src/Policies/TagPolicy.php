<?php

declare(strict_types=1);

namespace Moox\Tag\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Tag\Models\Tag;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Tag');
    }

    public function view(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('View:Tag');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Tag');
    }

    public function update(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('Update:Tag');
    }

    public function delete(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('Delete:Tag');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Tag');
    }

    public function restore(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('Restore:Tag');
    }

    public function forceDelete(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('ForceDelete:Tag');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Tag');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Tag');
    }

    public function replicate(AuthUser $authUser, Tag $tag): bool
    {
        return $authUser->can('Replicate:Tag');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Tag');
    }

}