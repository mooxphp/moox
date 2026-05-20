<?php

declare(strict_types=1);

namespace Moox\UserDevice\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\UserDevice\Models\UserDevice;
use Symfony\Component\HttpFoundation\Response;

class SyncDeviceIdToSessionRow
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('user-device.enabled', false)) {
            return $next($request);
        }

        $response = $next($request);

        $user = filament()->auth()->user() ?? Auth::user();

        if (! $user || ! method_exists($user, 'getAuthIdentifier')) {
            return $response;
        }

        if (! Schema::hasTable('sessions') || ! Schema::hasColumn('sessions', 'device_id')) {
            return $response;
        }

        $sessionId = session()->getId();

        if (blank($sessionId)) {
            return $response;
        }

        $deviceId = session()->get('user_device_id');

        if (blank($deviceId)) {
            /** @var mixed $userId */
            $userId = $user->getAuthIdentifier();

            $deviceId = UserDevice::query()
                ->where('user_id', $userId)
                ->where('user_type', $user::class)
                ->where('ip_address', $request->ip())
                ->latest('updated_at')
                ->value('id');

            if (filled($deviceId)) {
                session()->put('user_device_id', (int) $deviceId);
            }
        }

        if (blank($deviceId)) {
            return $response;
        }

        DB::table('sessions')->where('id', $sessionId)->update(['device_id' => (int) $deviceId]);

        return $response;
    }
}
