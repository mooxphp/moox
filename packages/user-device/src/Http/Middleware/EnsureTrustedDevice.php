<?php

declare(strict_types=1);

namespace Moox\UserDevice\Http\Middleware;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\UserDevice\Models\UserDevice;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureTrustedDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('user-device.enabled', false)) {
            return $next($request);
        }

        if (! config('user-device.enforce_trust', true)) {
            return $next($request);
        }

        $user = filament()->auth()->user() ?? Auth::user();

        if (! $user) {
            return $next($request);
        }

        if ($this->isShieldAdmin($user)) {
            return $next($request);
        }

        $sessionId = session()->getId();

        if (blank($sessionId)) {
            return $next($request);
        }

        $deviceId = session()->get('user_device_id');

        if (blank($deviceId) && Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'device_id')) {
            $deviceId = DB::table('sessions')->where('id', $sessionId)->value('device_id');
        }

        // Fallback: resolve device by user+ip (covers session-regeneration edge cases).
        // Use the Filament-authenticated user id — Auth::id() is the wrong guard on portal.
        if (blank($deviceId)) {
            $userId = $user->getAuthIdentifier();
            if (blank($userId)) {
                return $next($request);
            }

            $deviceId = UserDevice::query()
                ->where('user_id', $userId)
                ->where('user_type', $user::class)
                ->where('ip_address', $request->ip())
                ->latest('updated_at')
                ->value('id');

            if (filled($deviceId)) {
                session()->put('user_device_id', (int) $deviceId);

                if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'device_id')) {
                    DB::table('sessions')->where('id', $sessionId)->update(['device_id' => (int) $deviceId]);
                }
            }
        }

        if (blank($deviceId)) {
            return $next($request);
        }

        $device = UserDevice::query()->find((int) $deviceId);

        // If we can't resolve the device record, don't lock users out.
        if (! $device) {
            return $next($request);
        }

        if ($device->whitelisted) {
            return $next($request);
        }

        // Allow trusting the device via magic-link even when untrusted.
        if ($request->route()?->getName() === 'user-device.devices.trust') {
            return $next($request);
        }

        // Untrusted: kick back to the login screen with a toast (no panel access).
        Notification::make()
            ->title(__('user-device::translations.device_blocked_title'))
            ->body(__('user-device::translations.device_blocked_body'))
            ->danger()
            ->send();

        filament()->auth()->logout();

        return redirect()->to($this->loginUrl());
    }

    private function loginUrl(): string
    {
        try {
            return (string) (filament()->getLoginUrl() ?? '/');
        } catch (Throwable) {
            return '/';
        }
    }

    private function isShieldAdmin(object $user): bool
    {
        if (! $this->permissionSystemAvailable()) {
            return false;
        }

        if (! method_exists($user, 'hasRole')) {
            return false;
        }

        $roleName = (string) config('filament-shield.super_admin.name', 'super_admin');

        return (bool) $user->hasRole($roleName);
    }

    private function permissionSystemAvailable(): bool
    {
        if (! class_exists(PermissionRegistrar::class)) {
            return false;
        }

        return Schema::hasTable('permissions') && Schema::hasTable('roles');
    }
}
