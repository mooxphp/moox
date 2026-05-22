<?php

declare(strict_types=1);

namespace Moox\BackupServerUi;

use Moox\BackupServerUi\Commands\InstallCommand;
use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class BackupServerUiServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('backup-server-ui')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasCommand(InstallCommand::class);
    }
}
