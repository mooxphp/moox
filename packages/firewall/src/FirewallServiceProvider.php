<?php

declare(strict_types=1);

namespace Moox\Firewall;

use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Moox\Firewall\Listeners\FirewallListener;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FirewallServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('firewall')
            ->hasConfigFile()
            ->hasViews('access-denied')
            ->hasViews('backdoor')
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'firewall');

        if (config('firewall.enabled', false)) {
            Event::listen(RouteMatched::class, [FirewallListener::class, 'handle']);

            \Log::info('🛡️ Moox Firewall is active');
        }
    }
}
