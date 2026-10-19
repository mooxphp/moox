<?php

declare(strict_types=1);

namespace Moox\Jobs\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Jobs\Models\FailedJob;

class FailedJobPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FailedJob');
    }

    public function view(AuthUser $authUser, FailedJob $failedJob): bool
    {
        return $authUser->can('View:FailedJob');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FailedJob');
    }

    public function update(AuthUser $authUser, FailedJob $failedJob): bool
    {
        return $authUser->can('Update:FailedJob');
    }

    public function delete(AuthUser $authUser, FailedJob $failedJob): bool
    {
        return $authUser->can('Delete:FailedJob');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FailedJob');
    }

    public function restore(AuthUser $authUser, FailedJob $failedJob): bool
    {
        return $authUser->can('Restore:FailedJob');
    }

    public function forceDelete(AuthUser $authUser, FailedJob $failedJob): bool
    {
        return $authUser->can('ForceDelete:FailedJob');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FailedJob');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FailedJob');
    }

    public function replicate(AuthUser $authUser, FailedJob $failedJob): bool
    {
        return $authUser->can('Replicate:FailedJob');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FailedJob');
    }
}
