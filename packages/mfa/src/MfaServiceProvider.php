<?php

declare(strict_types=1);

namespace Moox\Mfa;

use Moox\Core\Installer\Contracts\AssetInstallerInterface;
use Moox\Core\MooxServiceProvider;
use Moox\Mfa\Installers\MfaInstaller;
use Spatie\LaravelPackageTools\Package;

class MfaServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('mfa')
            ->hasConfigFile()
            ->hasMigrations([
                'add_app_authentication_columns_to_users_table',
            ]);
    }

    /**
     * @return array<AssetInstallerInterface>
     */
    public function getCustomInstallers(): array
    {
        return [
            new MfaInstaller,
        ];
    }

    /**
     * @return array<int, array{type: string, data: array<int, string>}>
     */
    public function getCustomInstallAssets(): array
    {
        return [
            [
                'type' => 'mfa-setup',
                'data' => [
                    'app-authentication-columns',
                ],
            ],
        ];
    }
}
