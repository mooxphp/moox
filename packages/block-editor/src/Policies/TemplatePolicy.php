<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Schema;
use Moox\BlockEditor\Models\Template;

class TemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(Authorizable $user): bool
    {
        return $this->allowsByPermission($user, 'ViewAny:Template');
    }

    public function view(Authorizable $user, Template $template): bool
    {
        return $this->allowsByPermission($user, 'View:Template');
    }

    public function create(Authorizable $user): bool
    {
        return $this->allowsByPermission($user, 'Create:Template');
    }

    public function update(Authorizable $user, Template $template): bool
    {
        return $this->allowsByPermission($user, 'Update:Template');
    }

    public function delete(Authorizable $user, Template $template): bool
    {
        return $this->allowsByPermission($user, 'Delete:Template');
    }

    protected function allowsByPermission(Authorizable $user, string $permissionName): bool
    {
        $configuredPermission = config('moox-editor.api.permissions.'.$this->permissionConfigKey($permissionName));

        if (is_string($configuredPermission) && $configuredPermission !== '') {
            $permissionName = $configuredPermission;
        }

        if (! $this->permissionSystemAvailable()) {
            return true;
        }

        if (! $this->permissionExists($permissionName)) {
            return true;
        }

        return $user->can($permissionName);
    }

    protected function permissionConfigKey(string $permissionName): string
    {
        return match ($permissionName) {
            'ViewAny:Template' => 'view_any',
            'View:Template' => 'view',
            'Create:Template' => 'create',
            'Update:Template' => 'update',
            'Delete:Template' => 'delete',
            default => 'view',
        };
    }

    protected function permissionSystemAvailable(): bool
    {
        if (! class_exists(\Spatie\Permission\Models\Permission::class)) {
            return false;
        }

        return Schema::hasTable('permissions');
    }

    protected function permissionExists(string $permissionName): bool
    {
        if (! class_exists(\Spatie\Permission\Models\Permission::class)) {
            return false;
        }

        return \Spatie\Permission\Models\Permission::query()
            ->where('name', $permissionName)
            ->exists();
    }
}
