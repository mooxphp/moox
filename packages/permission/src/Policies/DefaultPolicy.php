<?php

namespace Moox\Permission\Policies;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class DefaultPolicy
{
    protected mixed $user;

    public function __construct($guard = null)
    {
        $guard ??= Auth::getDefaultDriver();

        $this->user = Auth::guard($guard)->user();
    }

    protected function hasPermission(string $permission): bool
    {
        if (! $this->permissionSystemAvailable()) {
            return true;
        }

        if (! $this->user || ! method_exists($this->user, 'hasPermissionTo')) {
            return true;
        }

        return $this->user->hasPermissionTo($permission);
    }

    protected function permissionSystemAvailable(): bool
    {
        if (! class_exists(PermissionRegistrar::class)) {
            return false;
        }

        return Schema::hasTable('permissions');
    }

    public function view(): bool
    {
        return $this->hasPermission('view');
    }

    public function edit(): bool
    {
        return $this->hasPermission('edit');
    }

    public function create(): bool
    {
        return $this->hasPermission('create');
    }

    public function delete(): bool
    {
        return $this->hasPermission('delete');
    }

    public function restore(): bool
    {
        return $this->hasPermission('restore');
    }

    public function publish(): bool
    {
        return $this->hasPermission('publish');
    }

    public function viewOwn($model): bool
    {
        return $this->hasPermission('view own') && $model->user_id === $this->user->id;
    }

    public function editOwn($model): bool
    {
        return $this->hasPermission('edit own') && $model->user_id === $this->user->id;
    }

    public function deleteOwn($model): bool
    {
        return $this->hasPermission('delete own') && $model->user_id === $this->user->id;
    }

    public function publishOwn($model): bool
    {
        return $this->hasPermission('publish own') && $model->user_id === $this->user->id;
    }

    public function bulkModify(): bool
    {
        return $this->hasPermission('bulk modify');
    }

    public function timeTravel(): bool
    {
        return $this->hasPermission('time travel');
    }

    public function forceDelete(): bool
    {
        return $this->hasPermission('force delete');
    }
}
