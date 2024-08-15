<?php

namespace Moox\Permission\Policies;

use App\Models\User;

class DefaultPolicy
{
    public function viewAll(User $user)
    {
        return $user->hasPermissionTo('view all');
    }

    public function editAll(User $user)
    {
        return $user->hasPermissionTo('edit all');
    }

    public function deleteAll(User $user)
    {
        return $user->hasPermissionTo('delete all');
    }

    public function create(User $user)
    {
        return $user->hasPermissionTo('create');
    }

    public function viewOwn(User $user, $model)
    {
        return $user->hasPermissionTo('view own') && $model->user_id === $user->id;
    }

    public function editOwn(User $user, $model)
    {
        return $user->hasPermissionTo('edit own') && $model->user_id === $user->id;
    }

    public function deleteOwn(User $user, $model)
    {
        return $user->hasPermissionTo('delete own') && $model->user_id === $user->id;
    }

    public function emptyTrash(User $user)
    {
        return $user->hasPermissionTo('empty trash');
    }

    public function changeSettings(User $user)
    {
        return $user->hasPermissionTo('change settings');
    }
}
