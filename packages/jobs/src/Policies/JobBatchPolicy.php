<?php

declare(strict_types=1);

namespace Moox\Jobs\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Jobs\Models\JobBatch;

class JobBatchPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:JobBatch');
    }

    public function view(AuthUser $authUser, JobBatch $jobBatch): bool
    {
        return $authUser->can('View:JobBatch');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:JobBatch');
    }

    public function update(AuthUser $authUser, JobBatch $jobBatch): bool
    {
        return $authUser->can('Update:JobBatch');
    }

    public function delete(AuthUser $authUser, JobBatch $jobBatch): bool
    {
        return $authUser->can('Delete:JobBatch');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:JobBatch');
    }

    public function restore(AuthUser $authUser, JobBatch $jobBatch): bool
    {
        return $authUser->can('Restore:JobBatch');
    }

    public function forceDelete(AuthUser $authUser, JobBatch $jobBatch): bool
    {
        return $authUser->can('ForceDelete:JobBatch');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:JobBatch');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:JobBatch');
    }

    public function replicate(AuthUser $authUser, JobBatch $jobBatch): bool
    {
        return $authUser->can('Replicate:JobBatch');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:JobBatch');
    }
}
