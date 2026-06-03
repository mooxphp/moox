<?php

declare(strict_types=1);

namespace Moox\BackupServerUi;

use Moox\BackupServerUi\Commands\InstallCommand;
use Moox\BackupServerUi\Installers\BackupServerInstaller;
use Moox\Core\Installer\Contracts\AssetInstallerInterface;
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

    /**
     * Custom-Installer für Spatie Laravel Backup Server (Config + Migrationen).
     *
     * @return array<AssetInstallerInterface>
     */
    public function getCustomInstallers(): array
    {
        return [
            new BackupServerInstaller,
        ];
    }

    /**
     * Custom-Assets, damit der Typ "backup-server-setup" im Moox-Installer auswählbar ist.
     */
    public function getCustomInstallAssets(): array
    {
        return [
            [
                'type' => 'backup-server-setup',
                'data' => [
                    'spatie-backup-server',
                ],
            ],
        ];
    }
}
