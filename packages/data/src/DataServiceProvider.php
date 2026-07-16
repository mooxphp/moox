<?php

declare(strict_types=1);

namespace Moox\Data;

use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Support\Facades\Blade;
use Moox\Core\Installer\Contracts\AssetInstallerInterface;
use Moox\Core\MooxServiceProvider;
use Moox\Data\Console\Commands\ImportCodelistsCommand;
use Moox\Data\Console\Commands\ImportStaticDataCommand;
use Moox\Data\Filament\Providers\DataPanelProvider;
use Moox\Data\Filament\Resources\StaticAllowanceReasonResource\Pages\ListStaticAllowanceReasons;
use Moox\Data\Filament\Resources\StaticChargeReasonResource\Pages\ListStaticChargeReasons;
use Moox\Data\Filament\Resources\StaticDocumentTypeResource\Pages\ListStaticDocumentTypes;
use Moox\Data\Filament\Resources\StaticEasSchemeResource\Pages\ListStaticEasSchemes;
use Moox\Data\Filament\Resources\StaticIcdSchemeResource\Pages\ListStaticIcdSchemes;
use Moox\Data\Filament\Resources\StaticIncotermResource\Pages\ListStaticIncoterms;
use Moox\Data\Filament\Resources\StaticPaymentMeanResource\Pages\ListStaticPaymentMeans;
use Moox\Data\Filament\Resources\StaticUnitResource\Pages\ListStaticUnits;
use Moox\Data\Filament\Resources\StaticVatCategoryResource\Pages\ListStaticVatCategories;
use Moox\Data\Filament\Resources\StaticVatExemptionReasonResource\Pages\ListStaticVatExemptionReasons;
use Moox\Data\Installers\StaticCodelistsInstaller;
use Moox\Data\Installers\StaticDataInstaller;
use Spatie\LaravelPackageTools\Package;

class DataServiceProvider extends MooxServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'data');
    }

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFiles();

        if (config('data.enable-panel')) {
            $this->app->register(DataPanelProvider::class);
        }
    }

    public function mergeConfigFiles()
    {
        $configs = [
            'rest-countries' => 'rest-countries',
            'data' => 'data/data',
            'static-countries-static-currencies' => 'data/static-countries-static-currencies',
            'static-countries-static-timezones' => 'data/static-countries-static-timezones',
            'static-country' => 'data/static-countries',
            'static-currency' => 'data/static-currencies',
            'static-language' => 'data/static-language',
            'static-locale' => 'data/static-locale',
            'static-timezone' => 'data/static-timezones',
            'static-charge-reason' => 'data/static-charge-reason',
            'static-allowance-reason' => 'data/static-allowance-reason',
            'static-document-type' => 'data/static-document-type',
            'static-vat-category' => 'data/static-vat-category',
            'static-payment-mean' => 'data/static-payment-mean',
            'static-unit' => 'data/static-unit',
            'static-incoterm' => 'data/static-incoterm',
            'static-vat-exemption-reason' => 'data/static-vat-exemption-reason',
            'static-icd-scheme' => 'data/static-icd-scheme',
            'static-eas-scheme' => 'data/static-eas-scheme',
        ];

        foreach ($configs as $file => $namespace) {
            $this->mergeConfigFrom(__DIR__."/../config/{$file}.php", $namespace);
        }
    }

    public function configureMoox(Package $package): void
    {
        $package
            ->name('data')
            ->hasConfigFile(['rest-countries', 'data', 'static-countries-static-currencies', 'static-countries-static-timezones', 'static-country', 'static-currency', 'static-language', 'static-locale', 'static-timezone', 'static-charge-reason', 'static-allowance-reason', 'static-document-type', 'static-vat-category', 'static-payment-mean', 'static-unit', 'static-incoterm', 'static-vat-exemption-reason', 'static-icd-scheme', 'static-eas-scheme'])
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
                'create_static_charge_reason_translations_table',
                'create_static_allowance_reasons_table',
                'create_static_allowance_reason_translations_table',
                'create_static_document_types_table',
                'create_static_document_type_translations_table',
                'create_static_vat_categories_table',
                'create_static_vat_category_translations_table',
                'create_static_payment_means_table',
                'create_static_payment_mean_translations_table',
                'create_static_units_table',
                'create_static_unit_translations_table',
                'create_static_incoterms_table',
                'create_static_incoterm_translations_table',
                'create_static_vat_exemption_reasons_table',
                'create_static_vat_exemption_reason_translations_table',
                'create_static_icd_schemes_table',
                'create_static_icd_scheme_translations_table',
                'create_static_eas_schemes_table',
                'create_static_eas_scheme_translations_table',
            ]);
    }

    public function packageBooted(): void
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
            fn (): string => Blade::render('@include("localization::lang-selector")'),
            scopes: [
                ListStaticAllowanceReasons::class,
                ListStaticChargeReasons::class,
                ListStaticDocumentTypes::class,
                ListStaticEasSchemes::class,
                ListStaticIcdSchemes::class,
                ListStaticIncoterms::class,
                ListStaticPaymentMeans::class,
                ListStaticUnits::class,
                ListStaticVatCategories::class,
                ListStaticVatExemptionReasons::class,
            ],
        );
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
