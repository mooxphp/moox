<?php

declare(strict_types=1);

namespace Moox\UserDevice\Listeners;

use Illuminate\Auth\Events\Login;
use Jenssegers\Agent\Agent;
use Moox\UserDevice\Services\UserDeviceTracker;

class TrackUserDeviceOnLogin
{
    public function handle(Login $event): void
    {
        if (! config('user-device.enabled', false)) {
            return;
        }

        app(UserDeviceTracker::class)->addUserDevice(request(), $event->user, app(Agent::class));
    }
}
