<?php

declare(strict_types=1);

namespace Moox\Notification;

use Moox\Notification\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class NotificationServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('notifications')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            // ->hasMigrations(['create_notifications_table']) only for testing purposes
            ->hasCommand(InstallCommand::class);
    }
}
