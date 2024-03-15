<?php

namespace App\Policies;

use Moox\User\Models\User;
use Moox\Sync\Models\Platform;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlatformPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \Moox\User\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_platform');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \Moox\User\Models\User  $user
     * @param  \Moox\Sync\Models\Platform  $platform
     * @return bool
     */
    public function view(User $user, Platform $platform): bool
    {
        return $user->can('view_platform');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \Moox\User\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('create_platform');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \Moox\User\Models\User  $user
     * @param  \Moox\Sync\Models\Platform  $platform
     * @return bool
     */
    public function update(User $user, Platform $platform): bool
    {
        return $user->can('update_platform');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \Moox\User\Models\User  $user
     * @param  \Moox\Sync\Models\Platform  $platform
     * @return bool
     */
    public function delete(User $user, Platform $platform): bool
    {
        return $user->can('delete_platform');
    }

    /**
     * Determine whether the user can bulk delete.
     *
     * @param  \Moox\User\Models\User  $user
     * @return bool
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_platform');
    }

    /**
     * Determine whether the user can permanently delete.
     *
     * @param  \Moox\User\Models\User  $user
     * @param  \Moox\Sync\Models\Platform  $platform
     * @return bool
     */
    public function forceDelete(User $user, Platform $platform): bool
    {
        return $user->can('force_delete_platform');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     *
     * @param  \Moox\User\Models\User  $user
     * @return bool
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_platform');
    }

    /**
     * Determine whether the user can restore.
     *
     * @param  \Moox\User\Models\User  $user
     * @param  \Moox\Sync\Models\Platform  $platform
     * @return bool
     */
    public function restore(User $user, Platform $platform): bool
    {
        return $user->can('restore_platform');
    }

    /**
     * Determine whether the user can bulk restore.
     *
     * @param  \Moox\User\Models\User  $user
     * @return bool
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_platform');
    }

    /**
     * Determine whether the user can replicate.
     *
     * @param  \Moox\User\Models\User  $user
     * @param  \Moox\Sync\Models\Platform  $platform
     * @return bool
     */
    public function replicate(User $user, Platform $platform): bool
    {
        return $user->can('replicate_platform');
    }

    /**
     * Determine whether the user can reorder.
     *
     * @param  \Moox\User\Models\User  $user
     * @return bool
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_platform');
    }

}
