<?php

namespace Moox\Sync\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Moox\Sync\Models\Sync;
use Illuminate\Contracts\Auth\Access\Authorizable;


class SyncPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Authorizable $user): bool
    {
        return $user->can('view_any_sync');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Authorizable $user, Sync $sync): bool
    {
        return $user->can('view_sync');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Authorizable $user): bool
    {
        return $user->can('create_sync');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Authorizable $user, Sync $sync): bool
    {
        return $user->can('update_sync');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Authorizable $user, Sync $sync): bool
    {
        return $user->can('delete_sync');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(Authorizable $user): bool
    {
        return $user->can('delete_any_sync');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(Authorizable $user, Sync $sync): bool
    {
        return $user->can('force_delete_sync');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(Authorizable $user): bool
    {
        return $user->can('force_delete_any_sync');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(Authorizable $user, Sync $sync): bool
    {
        return $user->can('restore_sync');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(Authorizable $user): bool
    {
        return $user->can('restore_any_sync');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(Authorizable $user, Sync $sync): bool
    {
        return $user->can('replicate_sync');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(Authorizable $user): bool
    {
        return $user->can('reorder_sync');
    }
}
