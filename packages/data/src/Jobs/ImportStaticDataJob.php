<?php

namespace Moox\Data\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Moox\Data\Models\StaticCountriesStaticCurrencies;
use Moox\Data\Models\StaticCountriesStaticTimezones;
use Moox\Data\Models\StaticCountry;
use Moox\Data\Models\StaticCurrency;
use Moox\Data\Models\StaticLanguage;
use Moox\Data\Models\StaticLocale;
use Moox\Data\Models\StaticTimezone;

class ImportStaticDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        Log::info('Starting import of static data from REST Countries API...');

        $alpha3ToAlpha2 = [
            'deu' => 'de',
            'fra' => 'fr',
            'por' => 'pt',
            'eng' => 'en',
            'spa' => 'es',
            'nld' => 'nl',
            'ara' => 'ar',
            'heb' => 'he',
            'mri' => 'mi',
            'nzs' => 'nzs',
            'kon' => 'kg',
            'lin' => 'ln',
            'smo' => 'sm',
            'mon' => 'mn',
        ];

        try {
            $response = Http::get('https://restcountries.com/v3.1/all');
            Log::info('API Response status: '.$response->status());

            if ($response->failed()) {
                Log::error('Failed to fetch data from REST Countries API. Status: '.$response->status());

                return;
            }

            $countries = $response->json();
            Log::info('Fetched '.count($countries).' countries from API');

            foreach ($countries as $countryData) {
                Log::info('Processing country: '.$countryData['cca2']);
                // Insert or update country
                $country = StaticCountry::updateOrCreate(
                    ['alpha2' => $countryData['cca2']],
                    [
                        'alpha3_b' => $countryData['cca3'] ?? null,
                        'common_name' => $countryData['name']['common'],
                        'native_name' => json_encode($countryData['name']['nativeName'] ?? []),
                        'exonyms' => json_encode($countryData['translations'] ?? []),
                        'region' => $countryData['region'] ?? null,
                        'subregion' => $countryData['subregion'] ?? null,
                        'calling_code' => $countryData['idd']['root'] ?? null,
                        'capital' => $countryData['capital'][0] ?? null,
                        'population' => $countryData['population'] ?? null,
                        'area' => $countryData['area'] ?? null,
                        'tlds' => json_encode($countryData['tld'] ?? []),
                        'membership' => json_encode($countryData['regionalBlocs'] ?? []),
                        'postal_code_regex' => $countryData['postalCode']['format'] ?? null,
                        'dialing_prefix' => $countryData['idd']['root'] ?? null,
                        'date_format' => 'YYYY-MM-DD',
                    ]
                );

                // Insert or update currencies
                if (! empty($countryData['currencies'])) {
                    foreach ($countryData['currencies'] as $code => $currencyData) {
                        $currency = StaticCurrency::updateOrCreate(
                            ['code' => $code],
                            [
                                'common_name' => $currencyData['name'] ?? '',
                                'symbol' => $currencyData['symbol'] ?? null,
                            ]
                        );
                        StaticCountriesStaticCurrencies::updateOrCreate([
                            'country_id' => $country->id,
                            'currency_id' => $currency->id,
                            'is_primary' => true,
                        ]);
                    }
                }

                // Insert or update languages
                if (! empty($countryData['languages'])) {
                    Log::info("Languages for {$country->alpha2}:", $countryData['languages']);
                    foreach ($countryData['languages'] as $code => $name) {
                        Log::info("Processing language: {$code} ({$name}) for country {$country->alpha2}");

                        $alpha2 = $alpha3ToAlpha2[$code] ?? $code;
                        Log::info("Converted {$code} to {$alpha2}");

                        $language = StaticLanguage::updateOrCreate(
                            ['alpha2' => $alpha2],
                            ['common_name' => $name]
                        );

                        $locale = $alpha2.'_'.$country->alpha2;
                        Log::info("Generated locale: {$locale}");

                        StaticLocale::where('country_id', $country->id)
                            ->where('language_id', $language->id)
                            ->update(['locale' => $locale]);

                        StaticLocale::updateOrCreate(
                            [
                                'country_id' => $country->id,
                                'language_id' => $language->id,
                            ],
                            [
                                'locale' => $locale,
                                'name' => $name,
                                'is_official_language' => true,
                            ]
                        );
                    }
                }

                // Insert or update timezones
                if (! empty($countryData['timezones'])) {
                    foreach ($countryData['timezones'] as $timezoneName) {
                        $timezone = StaticTimezone::updateOrCreate(
                            ['name' => $timezoneName],
                            ['offset_standard' => '', 'dst' => false]
                        );
                        StaticCountriesStaticTimezones::updateOrCreate([
                            'country_id' => $country->id,
                            'timezone_id' => $timezone->id,
                        ]);
                    }
                }
            }

            Log::info('Finished importing static data from REST Countries API.');
        } catch (\Exception $e) {
            Log::error('Error during import: '.$e->getMessage());
        }
    }
}
