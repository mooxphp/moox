<?php

declare(strict_types=1);

namespace Moox\User;

use Illuminate\Support\Facades\Gate;
use Moox\User\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UserServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('user')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations(['update_user_table'])
            ->hasCommand(InstallCommand::class);
    }

    public function packageBooted(): void
    {
        Gate::guessPolicyNamesUsing(function (string $modelClass): string {
            $baseName = class_basename($modelClass);

            $appPolicy = "App\\Policies\\{$baseName}Policy";

            if (class_exists($appPolicy)) {
                return $appPolicy;
            }

            return str_replace('\\Models\\', '\\Policies\\', $modelClass).'Policy';
        });
    }
}
