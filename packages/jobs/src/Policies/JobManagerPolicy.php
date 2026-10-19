<?php

declare(strict_types=1);

namespace Moox\Jobs\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Jobs\Models\JobManager;

class JobManagerPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:JobManager');
    }

    public function view(AuthUser $authUser, JobManager $jobManager): bool
    {
        return $authUser->can('View:JobManager');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:JobManager');
    }

    public function update(AuthUser $authUser, JobManager $jobManager): bool
    {
        return $authUser->can('Update:JobManager');
    }

    public function delete(AuthUser $authUser, JobManager $jobManager): bool
    {
        return $authUser->can('Delete:JobManager');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:JobManager');
    }

    public function restore(AuthUser $authUser, JobManager $jobManager): bool
    {
        return $authUser->can('Restore:JobManager');
    }

    public function forceDelete(AuthUser $authUser, JobManager $jobManager): bool
    {
        return $authUser->can('ForceDelete:JobManager');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:JobManager');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:JobManager');
    }

    public function replicate(AuthUser $authUser, JobManager $jobManager): bool
    {
        return $authUser->can('Replicate:JobManager');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:JobManager');
    }
}
