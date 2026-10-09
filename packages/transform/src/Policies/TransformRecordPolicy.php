<?php

declare(strict_types=1);

namespace Moox\Transform\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Transform\Models\TransformRecord;

class TransformRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TransformRecord');
    }

    public function view(AuthUser $authUser, TransformRecord $transformRecord): bool
    {
        return $authUser->can('View:TransformRecord');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TransformRecord');
    }

    public function update(AuthUser $authUser, TransformRecord $transformRecord): bool
    {
        return $authUser->can('Update:TransformRecord');
    }

    public function delete(AuthUser $authUser, TransformRecord $transformRecord): bool
    {
        return $authUser->can('Delete:TransformRecord');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TransformRecord');
    }

    public function restore(AuthUser $authUser, TransformRecord $transformRecord): bool
    {
        return $authUser->can('Restore:TransformRecord');
    }

    public function forceDelete(AuthUser $authUser, TransformRecord $transformRecord): bool
    {
        return $authUser->can('ForceDelete:TransformRecord');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TransformRecord');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TransformRecord');
    }

    public function replicate(AuthUser $authUser, TransformRecord $transformRecord): bool
    {
        return $authUser->can('Replicate:TransformRecord');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TransformRecord');
    }
}
