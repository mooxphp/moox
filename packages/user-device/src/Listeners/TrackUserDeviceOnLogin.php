<?php

declare(strict_types=1);

namespace Moox\UserDevice\Listeners;

use Illuminate\Auth\Events\Login;
use Jenssegers\Agent\Agent;
use Moox\UserDevice\Services\UserDeviceTracker;
use Moox\UserDevice\Support\UserDevicePanel;

class TrackUserDeviceOnLogin
{
    public function handle(Login $event): void
    {
        // Requires enabled=true AND UserDevicePlugin on the current panel.
        if (! UserDevicePanel::isActive()) {
            return;
        }

        app(UserDeviceTracker::class)->addUserDevice(request(), $event->user, app(Agent::class));
    }
}
