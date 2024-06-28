<?php

declare(strict_types=1);

namespace Moox\Notification;

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
        Route::get('notification',[NotificationController::class,'index']);
    }
}
