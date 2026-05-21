<?php

namespace Moox\Firewall\Plugins;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Moox\Firewall\Http\Middleware\EnsureFirewallAccess;
use Moox\Firewall\Resources\FirewallWhitelistEntryResource;

class FirewallPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'firewall';
    }

    public function register(Panel $panel): void
    {
        if (! (bool) config('firewall.enabled', false)) {
            return;
        }

        if (! (bool) config('firewall.resource.enabled', true)) {
            return;
        }

        // Important: Filament panel routes may not always be part of Laravel's `web` middleware group.
        // Attach firewall middleware to panels; what to protect/bypass is controlled via config.
        $panel->middleware([
            EnsureFirewallAccess::class,
        ]);

        $panel->resources([
            FirewallWhitelistEntryResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
