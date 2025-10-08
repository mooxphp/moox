<?php

declare(strict_types=1);

namespace Moox\Press;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel as BaseHttpKernel;
use Moox\Press\Commands\InstallCommand;
use Override;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PressServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('press')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasCommands(
                InstallCommand::class,
            )
            ->hasRoutes(['web']);
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(HttpKernel::class, function ($app) {
            return new class($app, $app['router']) extends BaseHttpKernel {
                public function handle($request)
                {
                    if (preg_match('#^/wp(-admin|-[^/]*\.php|/.*|$)#', $request->getRequestUri())) {
                        require_once base_path('public/wp/index.php');
                        exit;
                    }

                    return parent::handle($request);
                }
            };
        });
    }
}
