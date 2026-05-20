<?php

namespace Moox\UserDevice\Services;

use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Agent\Agent;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Notifications\NewDeviceNotification;

class UserDeviceTracker
{
    public function __construct(protected LocationService $locationService) {}

    public function addUserDevice(Request $request, Authenticatable $user, Agent $agent): void
    {
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();
        $user_id = $user->getAuthIdentifier();
        $location = $this->locationService->getLocation($ipAddress) ?? [];

        $agent->setUserAgent($userAgent);
        $browser = $agent->browser();
        $os = $agent->platform();
        $platform = $agent->isMobile() ? 'Mobile' : 'Desktop';

        $title = $platform.' '.$browser.' on '.$os.' in '.($location['city'] ?? 'Unknown').' - '.($location['country'] ?? 'Unknown');

        $device = UserDevice::updateOrCreate([
            'user_id' => $user_id,
            'user_type' => $user::class,
            'ip_address' => $ipAddress,
        ], [
            'title' => $title,
            'active' => true,
            'os' => $os,
            'platform' => $platform,
            'browser' => $browser,
            'city' => $location['city'] ?? null,
            'country' => $location['country'] ?? null,
            'location' => $location,
            'user_agent' => $userAgent,
        ]);

        // Persist the current device id in the session payload so enforcement can work
        // even when the database session row doesn't exist yet at login time.
        session()->put('user_device_id', $device->getKey());

        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'device_id')) {
            $sessionId = session()->getId();
            if (filled($sessionId)) {
                DB::table('sessions')->where('id', $sessionId)->update(['device_id' => $device->id]);
            }
        } else {
            Log::warning('The session-table does not have a device_id column. Install Moox User Devices package to add this feature.');
        }

        if ($device->wasRecentlyCreated && config('user-device.new_device_notification') && method_exists($user, 'notify')) {
            $panelId = class_exists(Filament::class)
                ? Filament::getCurrentPanel()?->getId()
                : null;

            $user->notify(new NewDeviceNotification([
                'title' => $title,
                'panel_id' => $panelId,
                'device_id' => $device->getKey(),
                'ip_address' => $ipAddress,
                'browser' => $browser,
                'os' => $os,
                'platform' => $platform,
                'city' => $location['city'] ?? null,
                'country' => $location['country'] ?? null,
            ]));
        }
    }
}
