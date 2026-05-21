<?php

declare(strict_types=1);

namespace Moox\User;

use Illuminate\Support\Facades\Gate;
use Moox\Core\MooxServiceProvider;
use Moox\User\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;

class UserServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('user')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations(['update_user_table']);
    }

    public function bootingPackage(): void
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
