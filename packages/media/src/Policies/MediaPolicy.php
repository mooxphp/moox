<?php

declare(strict_types=1);

namespace Moox\Media\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Media\Models\Media;

class MediaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Media');
    }

    public function view(AuthUser $authUser, Media $media): bool
    {
        return $authUser->can('View:Media');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Media');
    }

    public function update(AuthUser $authUser, Media $media): bool
    {
        return $authUser->can('Update:Media');
    }

    public function delete(AuthUser $authUser, Media $media): bool
    {
        return $authUser->can('Delete:Media');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Media');
    }

    public function restore(AuthUser $authUser, Media $media): bool
    {
        return $authUser->can('Restore:Media');
    }

    public function forceDelete(AuthUser $authUser, Media $media): bool
    {
        return $authUser->can('ForceDelete:Media');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Media');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Media');
    }

    public function replicate(AuthUser $authUser, Media $media): bool
    {
        return $authUser->can('Replicate:Media');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Media');
    }
}
