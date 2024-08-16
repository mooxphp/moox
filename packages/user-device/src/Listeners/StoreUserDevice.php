<?php

namespace Moox\UserDevice\Listeners;

use GeoIp2\Database\Reader;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Agent\Agent;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Services\LocationService;

class StoreUserDevice
{
    protected $request;

    protected $reader;

    protected $agent;

    protected $locationService;

    public function __construct(Request $request, Agent $agent, LocationService $locationService)
    {
        $this->request = $request;
        $this->agent = $agent;
        $this->locationService = $locationService;
        $this->reader = new Reader(__DIR__.'/../../database/geoip/GeoLite2-City.mmdb');
    }

    public function handle(Login $event)
    {
        $user = $event->user;
        $ipAddress = $this->request->ip();
        $userAgent = $this->request->userAgent();
        $user_id = $user->getAuthIdentifier();
        $location = $this->locationService->getLocation($ipAddress);

        $this->agent->setUserAgent($userAgent);
        $browser = $this->agent->browser();
        $os = $this->agent->platform();
        $platform = $this->agent->isMobile() ? 'Mobile' : 'Desktop';

        $title = $platform.' '.$browser.' on '.$os.' in '.($location['city'] ?? '- Unknown').' - '.($location['country'] ?? null);

        /** @disregard Non static method 'updateOrCreate' should not be called statically.intelephense(P1036) */
        $device = UserDevice::updateOrCreate([
            'user_id' => $user_id,
            'user_type' => get_class($user),
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
            $debug = DB::table('sessions')->where('id', session()->getId())->get();
            var_dump($debug);
            $debug = DB::table('sessions')->where('id', session()->getId())->update(['device_id' => $device->id]);
            dd($debug);
        } else {
            Log::warning('The session-table does not have a device_id column. Install Moox User Devices package to add this feature.');
        }

        if ($device->wasRecentlyCreated) {
            // TODO: Send a notification to the user about the new device.
        }
    }
}
