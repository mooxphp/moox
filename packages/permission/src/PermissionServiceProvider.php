<?php

declare(strict_types=1);

namespace Moox\Permission;

use Moox\Core\MooxServiceProvider;
use Moox\Permission\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;

class PermissionServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('moox-permission')
            ->hasConfigFile('permission')
            ->hasTranslations()
            ->hasMigrations(['create_permissions_table'])
            ->hasCommand(InstallCommand::class);
    }
}
