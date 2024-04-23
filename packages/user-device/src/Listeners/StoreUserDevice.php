<?php

namespace Moox\UserDevice\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Moox\UserDevice\Models\UserDevice;

class StoreUserDevice
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(Login $event)
    {
        $user = $event->user;
        $ipAddress = $this->request->ip();
        $userAgent = $this->request->userAgent();
        $user_id = $user->getAuthIdentifier();

        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        $browser = $agent->browser();
        $os = $agent->platform();

        $device = UserDevice::firstOrCreate([
            'user_id' => $user_id,
            'user_type' => get_class($user),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ], [
            'title' => $userAgent,
            'slug' => $userAgent,
            'active' => true,
            'os' => $os,
            'browser' => $browser,
            'country' => $userAgent,
            'location' => $userAgent,
            'whitelisted' => true,
        ]);

        if ($device->wasRecentlyCreated) {
            // currently this is an unknown device
        }
    }
}
