<?php

declare(strict_types=1);

namespace Moox\Scopes;

use Illuminate\Support\Facades\Gate;
use Moox\Core\Installer\Contracts\AssetInstallerInterface;
use Moox\Core\Models\Scope;
use Moox\Core\MooxServiceProvider;
use Moox\Scopes\Installers\ScopesInstaller;
use Moox\Scopes\Policies\ScopePolicy;
use Spatie\LaravelPackageTools\Package;

class ScopesServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('scopes')
            ->hasConfigFile()
            ->hasTranslations();
    }

    public function bootingPackage(): void
    {
        Gate::policy(Scope::class, ScopePolicy::class);
    }

    /**
     * Custom-Installer für das Scopes-Package, vom Moox-Installer ausgewertet.
     *
     * @return array<AssetInstallerInterface>
     */
    public function getCustomInstallers(): array
    {
        return [
            new ScopesInstaller,
        ];
    }

    /**
     * Custom-Assets, damit der Typ "scopes" im Installer auswählbar ist.
     */
    public function getCustomInstallAssets(): array
    {
        return [
            [
                'type' => 'scopes',
                'data' => [
                    'sync-scopes-from-config',
                ],
            ],
        ];
    }
}
