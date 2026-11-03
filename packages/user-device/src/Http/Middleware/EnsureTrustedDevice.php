<?php

declare(strict_types=1);

namespace Moox\UserDevice\Http\Middleware;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Agent\Agent;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Services\UserDeviceTracker;
use Moox\UserDevice\Support\UserDeviceAuthorization;
use STS\FilamentImpersonate\Facades\Impersonation;
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

        if (class_exists(Impersonation::class) && Impersonation::isImpersonating()) {
            return $next($request);
        }

        $user = filament()->auth()->user() ?? Auth::user();

        if (! $user instanceof Authenticatable) {
            return $next($request);
        }

        // Allow trusting the device via magic-link even when untrusted.
        if ($request->route()?->getName() === 'user-device.devices.trust') {
            return $next($request);
        }

        $device = $this->resolveDevice($request, $user);

        if ($device === null || ! $device->whitelisted) {
            return $this->blockUntrusted();
        }

        return $next($request);
    }

    private function resolveDevice(Request $request, Authenticatable $user): ?UserDevice
    {
        $sessionId = session()->getId();
        $deviceId = session()->get('user_device_id');

        if (blank($deviceId) && filled($sessionId) && Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'device_id')) {
            $deviceId = DB::table('sessions')->where('id', $sessionId)->value('device_id');
        }

        $device = $this->ownedDevice((int) $deviceId, $user);

        // Fallback: resolve device by user+ip (covers session-regeneration edge cases).
        // Use the Filament-authenticated user id — Auth::id() is the wrong guard on portal.
        if ($device === null) {
            $userId = $user->getAuthIdentifier();

            if (filled($userId)) {
                $deviceId = UserDevice::query()
                    ->where('user_id', $userId)
                    ->where('user_type', $user::class)
                    ->where('ip_address', $request->ip())
                    ->latest('updated_at')
                    ->value('id');

                $device = $this->ownedDevice((int) $deviceId, $user);
            }
        }

        // Last resort: track/create for this request. Never fail-open under enforce_trust —
        // production behind a proxy often loses session device_id and sees a different IP
        // than the login request, which previously skipped the gate entirely.
        if ($device === null) {
            session()->forget('user_device_id');
            app(UserDeviceTracker::class)->addUserDevice($request, $user, app(Agent::class));
            $device = $this->ownedDevice((int) session()->get('user_device_id'), $user);
        }

        if ($device === null) {
            return null;
        }

        session()->put('user_device_id', $device->getKey());

        if (filled($sessionId) && Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'device_id')) {
            DB::table('sessions')->where('id', $sessionId)->update(['device_id' => $device->getKey()]);
        }

        return $device;
    }

    /**
     * Only accept device rows that belong to the authenticated user.
     * Rejects poisoned session/device_id values pointing at another account.
     */
    private function ownedDevice(int $deviceId, Authenticatable $user): ?UserDevice
    {
        if ($deviceId <= 0) {
            return null;
        }

        $device = UserDevice::query()->find($deviceId);

        if ($device === null || ! UserDeviceAuthorization::ownsRecord($user, $device)) {
            return null;
        }

        return $device;
    }

    private function blockUntrusted(): Response
    {
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
}
