<?php

declare(strict_types=1);

namespace Moox\Restore;

use Illuminate\Support\Facades\Event;
use Moox\Core\MooxServiceProvider;
use Moox\Restore\Commands\DispatchRestoreCommand;
use Moox\Restore\Commands\InstallCommand;
use Moox\Restore\Commands\RestoreCommand;
use Moox\Restore\Commands\ServerSummaryCommand;
use Moox\Restore\Events\RestoreCompletedEvent;
use Moox\Restore\Events\RestoreFailedEvent;
use Moox\Restore\Events\RestoreStartedEvent;
use Moox\Restore\Listeners\RestoreBackupListener;
use Spatie\LaravelPackageTools\Package;

class RestoreServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('restore')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations(['create_restore_table'])
            ->hasCommands([
                InstallCommand::class,
                RestoreCommand::class,
                DispatchRestoreCommand::class,
                ServerSummaryCommand::class,
            ]);
    }

    public function bootingPackage(): void
    {
        parent::bootingPackage();

        Event::listen(
            RestoreCompletedEvent::class,
            [RestoreBackupListener::class, 'handle']
        );

        Event::listen(
            RestoreFailedEvent::class,
            [RestoreBackupListener::class, 'handle']
        );

        Event::listen(
            RestoreStartedEvent::class,
            [RestoreBackupListener::class, 'handle']
        );
    }
}
