<?php

declare(strict_types=1);

namespace Moox\Notification;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Moox\Notification\Commands\InstallCommand;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Moox\Notification\Http\Controllers\NotificationController;

class NotificationServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('notifications')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations(['create_notifications_table'])
            ->hasCommand(InstallCommand::class);
    }

    public function packageRegistered()
    {
        if (config('notifications.api')) {
            Route::get('/api/notifications/user/{user}/notificationBell', [NotificationController::class, 'getView']);
        }
    }
}
