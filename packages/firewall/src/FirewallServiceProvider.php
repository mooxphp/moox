<?php

declare(strict_types=1);

namespace Moox\Firewall;

use Moox\Core\MooxServiceProvider;
use Moox\Firewall\Middleware\FirewallMiddleware;
use Spatie\LaravelPackageTools\Package;

class FirewallServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('firewall')
            ->hasConfigFile()
            ->hasViews('firewall')
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Firewall')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                '%%UsedFor%%',
            ])
            ->alternatePackages([
                '', // optional alternative package (e.g. moox/post)
            ])
            ->templateFor([
                'creating simple Laravel packages',
            ])

            ->templateRemove([
                'build.php',
            ]);
    }

    public function packageBooted(): void
    {
        $this->app['router']->aliasMiddleware('firewall', FirewallMiddleware::class);

        if (config('firewall.global_enabled', false)) {
            $this->app['router']->pushMiddlewareToGroup('web', FirewallMiddleware::class);
            $this->app['router']->pushMiddlewareToGroup('api', FirewallMiddleware::class);
        }
    }

    public function boot(): void
    {
        parent::boot();

        // das tut!
        // dd('Views loaded');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'firewall');
    }
}
