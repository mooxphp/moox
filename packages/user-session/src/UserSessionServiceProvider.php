<?php

declare(strict_types=1);

namespace Moox\UserSession;

use Illuminate\Routing\Router;
use Moox\UserSession\Commands\InstallCommand;
use Moox\UserSession\Http\Middleware\StoreRelationsInSession;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UserSessionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('user-session')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommand(InstallCommand::class);
    }

    public function boot()
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_sessions_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_01_create_sessions_table.php'),
            ], 'create-sessions-table');

            $this->publishes([
                __DIR__.'/../database/migrations/extend_sessions_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_02_extend_sessions_table.php'),
            ], 'extend-sessions-table');
        }

        //$router = $this->app->make(Router::class);
        //$router->pushMiddlewareToGroup('web', StoreRelationsInSession::class);
    }
}
