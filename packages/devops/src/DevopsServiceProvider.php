<?php

declare(strict_types=1);

namespace Moox\Devops;

use Moox\Devops\Commands\InstallCommand;
use Moox\Devops\Commands\SyncForgeData;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DevopsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('devops')
            ->hasConfigFile()
            ->hasRoute('api')
            ->hasTranslations()
            ->hasMigrations([
                'create_moox_servers_table',
                'create_moox_projects_table',
                'create_github_commits_table',
                'create_github_repositories_table',
                'create_github_issues_table'])
            ->hasCommands(InstallCommand::class, SyncForgeData::class);
    }
}
