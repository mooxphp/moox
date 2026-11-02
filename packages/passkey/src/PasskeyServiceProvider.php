<?php

declare(strict_types=1);

namespace Moox\Passkey;

use Laravel\Passkeys\Passkeys;
use Moox\Core\Installer\Contracts\AssetInstallerInterface;
use Moox\Core\MooxServiceProvider;
use Moox\Passkey\Installers\PasskeyInstaller;
use Spatie\LaravelPackageTools\Package;

class PasskeyServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('passkey')
            ->hasConfigFile();

        // Upstream laravel/passkeys publishes its own migrations (passkeys-migrations).
    }

    public function register(): void
    {
        parent::register();

        // Must run in register() so the static flag is set before
        // Laravel\Passkeys\PasskeysServiceProvider::boot() loads routes.
        if (! config('passkey.enabled', false)) {
            Passkeys::ignoreRoutes();
        }
    }

    /**
     * @return array<AssetInstallerInterface>
     */
    public function getCustomInstallers(): array
    {
        return [
            new PasskeyInstaller,
        ];
    }

    /**
     * @return array<int, array{type: string, data: array<int, string>}>
     */
    public function getCustomInstallAssets(): array
    {
        return [
            [
                'type' => 'passkey-setup',
                'data' => [
                    'laravel-passkeys-publish',
                ],
            ],
        ];
    }
}
