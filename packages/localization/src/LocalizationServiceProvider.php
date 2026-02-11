<?php

declare(strict_types=1);

namespace Moox\Localization;

use Livewire\Livewire;
use Moox\Core\MooxServiceProvider;
use Moox\Localization\Filament\Providers\LocalizationPanelProvider;
use Moox\Localization\Installers\DefaultEnglishLocalizationInstaller;
use Moox\Localization\Livewire\LanguageSwitch;
use Spatie\LaravelPackageTools\Package;

class LocalizationServiceProvider extends MooxServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->mergeConfigFiles();

        $this->registerLivewireComponents();

        $this->registerFilamentPanel();
    }

    public function mergeConfigFiles()
    {
        $configs = [
            'localization' => 'localization/localization',
        ];

        foreach ($configs as $file => $namespace) {
            $this->mergeConfigFrom(__DIR__."/../config/{$file}.php", $namespace);
        }
    }

    public function configureMoox(Package $package): void
    {
        $package
            ->name('localization')
            ->hasConfigFile(['localization'])
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands()
            ->hasMigration('create_localizations_table');
    }

    public function registerLivewireComponents()
    {
        if (class_exists(Livewire::class)) {
            Livewire::component('language-switch', LanguageSwitch::class);
        }
    }

    public function registerFilamentPanel()
    {
        if (config('localization.enable-panel')) {
            $this->app->register(LocalizationPanelProvider::class);
        }
    }

    /**
     * Optional: vom Moox-Installer auswertbare Custom-Installer
     *
     * @return array<\Moox\Core\Installer\Contracts\AssetInstallerInterface>
     */
    public function getCustomInstallers(): array
    {
        return [
            new DefaultEnglishLocalizationInstaller,
        ];
    }

    public function getCustomInstallAssets(): array
    {
        return [
            [
                'type' => 'localizations',          // ← exakt wie getType()
                'data' => ['default-english'],     // Inhalt egal, wird von deinem Installer faktisch ignoriert
            ],
        ];
    }
}
