<?php

declare(strict_types=1);

namespace Moox\Press;

use Override;
use Illuminate\Support\Facades\Auth;
use Moox\Press\Commands\InstallCommand;
use Moox\Press\Commands\InstallWordPress;
use Moox\Press\Commands\UpdateWordPressPlugin;
use Moox\Press\Commands\UpdateWordPressURL;
use Moox\Press\Providers\WordPressUserProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PressServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('press')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasRoutes(['api', 'web'])
            ->hasCommands(
                InstallCommand::class,
                InstallWordPress::class,
                UpdateWordPressURL::class,
                UpdateWordPressPlugin::class,
            );
    }

    #[Override]
    public function boot(): void
    {
        parent::boot();

        Auth::provider('wpuser-provider', fn($app, array $config): WordPressUserProvider => new WordPressUserProvider($app['hash'], $config['model']));
    }
}
