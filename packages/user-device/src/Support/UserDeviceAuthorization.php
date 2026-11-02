<?php

declare(strict_types=1);

namespace Moox\UserDevice\Support;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Shared AuthZ helpers for UserDevice policy + Filament resource scoping.
 *
 * - ViewAny / View / Update / Delete: standard Shield CRUD on UserDevice
 * - ViewAll:UserDevice: see and manage every device (ops / "finance admin")
 * - Without ViewAll: list/view/update only own devices (user_id + user_type)
 */
final class UserDeviceAuthorization
{
    public const VIEW_ANY = 'ViewAny:UserDevice';

    public const VIEW = 'View:UserDevice';

    public const UPDATE = 'Update:UserDevice';

    public const DELETE = 'Delete:UserDevice';

    public const DELETE_ANY = 'DeleteAny:UserDevice';

    public const VIEW_ALL = 'ViewAll:UserDevice';

    public static function permissionSystemAvailable(): bool
    {
        if (! class_exists(PermissionRegistrar::class)) {
            return false;
        }

        return Schema::hasTable('permissions') && Schema::hasTable('roles');
    }

    public static function can(?object $user, string $permission): bool
    {
        if ($user === null || ! method_exists($user, 'can')) {
            return false;
        }

        try {
            return (bool) $user->can($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        } catch (Throwable) {
            return false;
        }
    }

    public static function canViewAll(?object $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (self::can($user, self::VIEW_ALL)) {
            return true;
        }

        if (! self::permissionSystemAvailable() || ! method_exists($user, 'hasRole')) {
            return false;
        }

        $roleName = (string) config('filament-shield.super_admin.name', 'super_admin');

        return (bool) $user->hasRole($roleName);
    }

    public static function ownsRecord(object $user, Model $record): bool
    {
        if (! method_exists($user, 'getAuthIdentifier')) {
            return false;
        }

        return ((string) $record->getAttribute('user_id') === (string) $user->getAuthIdentifier())
            && ((string) $record->getAttribute('user_type') === $user::class);
    }

    public static function allowOpsWithoutShield(): bool
    {
        return ! self::permissionSystemAvailable()
            && (bool) config('user-device.allow_all_devices_without_shield', false);
    }

    public static function canViewAny(?Authorizable $user): bool
    {
        if ($user === null) {
            return false;
        }

        // Without Shield tables, keep previous behaviour (menu visible).
        if (! self::permissionSystemAvailable()) {
            return true;
        }

        return self::can($user, self::VIEW_ANY);
    }
}
