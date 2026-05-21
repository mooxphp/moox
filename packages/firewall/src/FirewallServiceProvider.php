<?php

declare(strict_types=1);

namespace Moox\Firewall;

use Illuminate\Support\Facades\Route;
use Moox\Core\MooxServiceProvider;
use Moox\Firewall\Http\Middleware\EnsureFirewallAccess;
use Override;
use Spatie\LaravelPackageTools\Package;

class FirewallServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('firewall')
            ->hasConfigFile()
            ->hasViews('access-denied')
            ->hasViews('backdoor')
            ->hasTranslations()
            ->hasMigrations([
                'create_firewall_whitelist_entries_table',
            ]);
    }

    #[Override]
    public function boot(): void
    {
        parent::boot();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'firewall');

        // Global firewall middleware (package-level). We also provide a dummy backdoor route
        // so the middleware can handle GET/POST on this path.
        $this->app['router']->pushMiddlewareToGroup('web', EnsureFirewallAccess::class);

        Route::middleware(['web'])
            ->match(
                ['GET', 'POST'],
                trim((string) config('firewall.backdoor_url', '/backdoor'), '/'),
                fn () => abort(404),
            );
    }
}
