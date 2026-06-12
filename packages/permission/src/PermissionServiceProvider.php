<?php

declare(strict_types=1);

namespace Moox\Permission;

use Moox\Core\Installer\Contracts\AssetInstallerInterface;
use Moox\Core\MooxServiceProvider;
use Moox\Permission\Installers\PermissionInstaller;
use Spatie\LaravelPackageTools\Package;

class PermissionServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package->name('moox-permission');
    }

    /**
     * @return array<AssetInstallerInterface>
     */
    public function getCustomInstallers(): array
    {
        return [
            new PermissionInstaller,
        ];
    }

    public function getCustomInstallAssets(): array
    {
        return [
            [
                'type' => 'permission-setup',
                'data' => [
                    'shield-setup',
                ],
            ],
        ];
    }
}
