<?php

declare(strict_types=1);

namespace Moox\FrontendAuth;

use Moox\Core\MooxServiceProvider;
use Moox\FrontendAuth\Http\Middleware\FrontendAuthMiddleware;
use Spatie\LaravelPackageTools\Package;

class FrontendAuthServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('moox-frontend-auth')
            ->hasConfigFile();
    }

    /**
     * Register the middleware alias in the application.
     */
    public function registerMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('moox.frontend-auth', FrontendAuthMiddleware::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Register the middleware alias.
        $this->registerMiddleware();

        // Automatically protect all routes in the `web` middleware group
        // (i.e. routes from both `routes/web.php` and `routes/web-frontend.php`).
        // The middleware itself will no-op when disabled.
        $this->app['router']->pushMiddlewareToGroup('web', FrontendAuthMiddleware::class);
    }
}
