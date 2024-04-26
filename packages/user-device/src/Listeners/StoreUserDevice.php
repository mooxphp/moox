<?php

namespace Moox\UserDevice\Listeners;

use GeoIp2\Database\Reader;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Services\LocationService;

class StoreUserDevice
{
    protected $request;

    protected $reader;

    public function __construct(Request $request)
    {
        $this->request = $request;

        $this->reader = new Reader(__DIR__.'/../../database/geoip/GeoLite2-City.mmdb');
    }

    public function handle(Login $event)
    {
        $user = $event->user;
        $ipAddress = $this->request->ip();
        $userAgent = $this->request->userAgent();
        $user_id = $user->getAuthIdentifier();
        $locationService = new LocationService();
        $location = $locationService->getLocation($ipAddress);

        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        $browser = $agent->browser();
        $os = $agent->platform();

        $platform = $agent->isMobile() ? 'Mobile' : 'Desktop';

        $title = $platform.' '.$browser.' on '.$os.' in '.($location['city'] ?? '- Unknown').' - '.($location['country'] ?? null);

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

        if ($device->wasRecentlyCreated) {
            // currently this is an unknown device
        }
    }
}
