<?php

namespace Moox\UserDevice\Services;

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

    public function addUserDevice(Request $request, $user, Agent $agent): void
    {
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();
        $user_id = $user->getAuthIdentifier();
        $location = $this->locationService->getLocation($ipAddress);

        $agent->setUserAgent($userAgent);
        $browser = $agent->browser();
        $os = $agent->platform();
        $platform = $agent->isMobile() ? 'Mobile' : 'Desktop';

        $title = $platform.' '.$browser.' on '.$os.' in '.($location['city'] ?? '- Unknown').' - '.($location['country'] ?? null);

        $device = UserDevice::updateOrCreate([
            'user_id' => $user_id,
            'user_type' => $user::class,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ], [
            'title' => $title,
            'active' => true,
            'os' => $os,
            'platform' => $platform,
            'browser' => $browser,
            'city' => $location['city'] ?? null,
            'country' => $location['country'] ?? null,
            'location' => json_encode($location),
            'whitelisted' => true,
        ]);

        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'device_id')) {
            sleep(1);
            DB::table('sessions')->where('id', session()->getId())->update(['device_id' => $device->id]);
        } else {
            Log::warning('The session-table does not have a device_id column. Install Moox User Devices package to add this feature.');
        }

        if ($device->wasRecentlyCreated && config('user-device.new_device_notification')) {
            $user->notify(new NewDeviceNotification([
                'title' => $title,
            ]));
        }
    }
}
