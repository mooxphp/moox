<?php

declare(strict_types=1);

namespace Moox\DataLegacy;

use Moox\Core\Installer\Contracts\AssetInstallerInterface;
use Moox\Core\MooxServiceProvider;
use Moox\DataLegacy\Console\Commands\ImportCodelistsCommand;
use Moox\DataLegacy\Console\Commands\ImportStaticDataCommand;
use Moox\DataLegacy\Filament\Providers\DataLegacyPanelProvider;
use Moox\DataLegacy\Installers\StaticCodelistsInstaller;
use Moox\DataLegacy\Installers\StaticDataInstaller;
use Spatie\LaravelPackageTools\Package;

class DataLegacyServiceProvider extends MooxServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'data-legacy');
    }

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFiles();

        if (config('data-legacy.enable-panel')) {
            $this->app->register(DataLegacyPanelProvider::class);
        }
    }

    public function mergeConfigFiles()
    {
        $configs = [
            'data-legacy' => 'data-legacy/data-legacy',
            'static-countries-static-currencies' => 'data-legacy/static-countries-static-currencies',
            'static-countries-static-timezones' => 'data-legacy/static-countries-static-timezones',
            'static-country' => 'data-legacy/static-countries',
            'static-currency' => 'data-legacy/static-currencies',
            'static-language' => 'data-legacy/static-language',
            'static-locale' => 'data-legacy/static-locale',
            'static-timezone' => 'data-legacy/static-timezones',
            'static-charge-reason' => 'data-legacy/static-charge-reason',
            'static-allowance-reason' => 'data-legacy/static-allowance-reason',
            'static-document-type' => 'data-legacy/static-document-type',
            'static-vat-category' => 'data-legacy/static-vat-category',
            'static-payment-mean' => 'data-legacy/static-payment-mean',
            'static-unit' => 'data-legacy/static-unit',
            'static-incoterm' => 'data-legacy/static-incoterm',
            'static-vat-exemption-reason' => 'data-legacy/static-vat-exemption-reason',
            'static-icd-scheme' => 'data-legacy/static-icd-scheme',
            'static-eas-scheme' => 'data-legacy/static-eas-scheme',
        ];

        foreach ($configs as $file => $namespace) {
            $this->mergeConfigFrom(__DIR__."/../config/{$file}.php", $namespace);
        }
    }

    public function configureMoox(Package $package): void
    {
        $package
            ->name('data-legacy')
            ->hasConfigFile(['data-legacy', 'static-countries-static-currencies', 'static-countries-static-timezones', 'static-country', 'static-currency', 'static-language', 'static-locale', 'static-timezone', 'static-charge-reason', 'static-allowance-reason', 'static-document-type', 'static-vat-category', 'static-payment-mean', 'static-unit', 'static-incoterm', 'static-vat-exemption-reason', 'static-icd-scheme', 'static-eas-scheme'])
            ->hasViews()
            ->hasTranslations()
            ->hasCommands([
                ImportStaticDataCommand::class,
                ImportCodelistsCommand::class,
            ])
            ->hasMigrations([
                'create_static_countries_table',
                'create_static_languages_table',
                'create_static_locales_table',
                'create_static_currencies_table',
                'create_static_timezones_table',
                'create_static_countries_static_currencies_table',
                'create_static_country_static_timezones_table',
                'create_static_charge_reasons_table',
                'create_static_allowance_reasons_table',
                'create_static_document_types_table',
                'create_static_vat_categories_table',
                'create_static_payment_means_table',
                'create_static_units_table',
                'create_static_incoterms_table',
                'create_static_vat_exemption_reasons_table',
                'create_static_icd_schemes_table',
                'create_static_eas_schemes_table',
            ]);
    }

    /**
     * Custom-Installer für das Data-Package, vom Moox-Installer ausgewertet.
     *
     * @return array<AssetInstallerInterface>
     */
    public function getCustomInstallers(): array
    {
        return [
            new StaticDataInstaller,
            new StaticCodelistsInstaller,
        ];
    }

    /**
     * Custom-Assets, damit der Typ "static-data" im Installer auswählbar ist.
     */
    public function getCustomInstallAssets(): array
    {
        return [
            [
                'type' => 'static-data',
                'data' => [
                    'import-rest-countries-static-data',
                ],
            ],
            [
                'type' => 'static-codelists',
                'data' => [
                    'import-committed-codelists',
                ],
            ],
        ];
    }
}
