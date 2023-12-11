<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Moox\Core\Models\Core;

class CorePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_core');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Core $core): bool
    {
        return $user->can('view_core');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_core');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Core $core): bool
    {
        return $user->can('update_core');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Core $core): bool
    {
        return $user->can('delete_core');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_core');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Core $core): bool
    {
        return $user->can('force_delete_core');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_core');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Core $core): bool
    {
        return $user->can('restore_core');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_core');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Core $core): bool
    {
        return $user->can('replicate_core');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_core');
    }
}
