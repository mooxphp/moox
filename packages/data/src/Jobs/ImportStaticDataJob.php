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
        Log::channel('daily')->info('Starting import of static data from REST Countries API...');

        $alpha3ToAlpha2 = [
            'afr' => 'af', // Afrikaans
            'amh' => 'am', // Amharic
            'ara' => 'ar', // Arabic
            'aze' => 'az', // Azerbaijani
            'bel' => 'be', // Belarusian
            'ben' => 'bn', // Bengali
            'bul' => 'bg', // Bulgarian
            'cat' => 'ca', // Catalan
            'ces' => 'cs', // Czech
            'dan' => 'da', // Danish
            'deu' => 'de', // German
            'div' => 'dv', // Divehi
            'dzo' => 'dz', // Dzongkha
            'ell' => 'el', // Greek
            'eng' => 'en', // English
            'est' => 'et', // Estonian
            'eus' => 'eu', // Basque
            'fas' => 'fa', // Persian
            'fil' => 'fl', // Filipino
            'fin' => 'fi', // Finnish
            'fra' => 'fr', // French
            'glc' => 'gl', // Galician
            'glv' => 'gv', // Manx
            'grn' => 'gn', // Guarani
            'heb' => 'he', // Hebrew
            'hin' => 'hi', // Hindi
            'hrv' => 'hr', // Croatian
            'hun' => 'hu', // Hungarian
            'hye' => 'hy', // Armenian
            'ind' => 'id', // Indonesian
            'isl' => 'is', // Icelandic
            'ita' => 'it', // Italian
            'jpn' => 'ja', // Japanese
            'kat' => 'ka', // Georgian
            'kaz' => 'kk', // Kazakh
            'khm' => 'km', // Khmer
            'kir' => 'ky', // Kyrgyz
            'kor' => 'ko', // Korean
            'lao' => 'lo', // Lao
            'lat' => 'la', // Latin
            'lav' => 'lv', // Latvian
            'lit' => 'lt', // Lithuanian
            'ltz' => 'lb', // Luxembourgish
            'mkd' => 'mk', // Macedonian
            'mlt' => 'mt', // Maltese
            'mon' => 'mn', // Mongolian
            'mri' => 'mi', // Maori
            'msa' => 'ms', // Malay
            'mya' => 'my', // Burmese
            'nep' => 'ne', // Nepali
            'nld' => 'nl', // Dutch
            'nor' => 'no', // Norwegian
            'nya' => 'ny', // Chichewa
            'pol' => 'pl', // Polish
            'por' => 'pt', // Portuguese
            'pus' => 'ps', // Pashto
            'ron' => 'ro', // Romanian
            'rus' => 'ru', // Russian
            'sin' => 'si', // Sinhala
            'slk' => 'sk', // Slovak
            'slv' => 'sl', // Slovenian
            'smo' => 'sm', // Samoan
            'sna' => 'sn', // Shona
            'som' => 'so', // Somali
            'spa' => 'es', // Spanish
            'sqi' => 'sq', // Albanian
            'srp' => 'sr', // Serbian
            'swa' => 'sw', // Swahili
            'swe' => 'sv', // Swedish
            'tam' => 'ta', // Tamil
            'tet' => 'te', // Telugu
            'tgk' => 'tg', // Tajik
            'tha' => 'th', // Thai
            'tir' => 'ti', // Tigrinya
            'ton' => 'to', // Tonga
            'tur' => 'tr', // Turkish
            'ukr' => 'uk', // Ukrainian
            'urd' => 'ur', // Urdu
            'uzb' => 'uz', // Uzbek
            'vie' => 'vi', // Vietnamese
            'zho' => 'zh', // Chinese
        ];

        try {
            Log::channel('daily')->info('Attempting to fetch data from REST Countries API...');
            $response = Http::timeout(60)->get('https://restcountries.com/v3.1/all');
            Log::channel('daily')->info('API Response status: '.$response->status());

            if ($response->failed()) {
                Log::channel('daily')->error('Failed to fetch data from REST Countries API. Status: '.$response->status());
                Log::channel('daily')->error('Response body: '.$response->body());

                return;
            }

            $countries = $response->json();
            Log::channel('daily')->info('Fetched '.count($countries).' countries from API');

            foreach ($countries as $countryData) {
                try {
                    Log::channel('daily')->info('Processing country: '.($countryData['cca2'] ?? 'unknown'));

                    if (! isset($countryData['cca2'])) {
                        Log::channel('daily')->warning('Skipping country - missing cca2 code');

                        continue;
                    }

                    $country = StaticCountry::updateOrCreate(
                        ['alpha2' => $countryData['cca2']],
                        [
                            'alpha3_b' => $countryData['cca3'] ?? null,
                            'common_name' => $countryData['name']['common'] ?? null,
                            'native_name' => json_encode($countryData['name']['nativeName'] ?? []),
                            'exonyms' => json_encode($countryData['translations'] ?? []),
                            'region' => $countryData['region'] ?? null,
                            'subregion' => $countryData['subregion'] ?? null,
                            'calling_code' => $countryData['idd']['root'] ?? null,
                            'capital' => is_array($countryData['capital']) ? ($countryData['capital'][0] ?? null) : $countryData['capital'],
                            'population' => $countryData['population'] ?? null,
                            'area' => $countryData['area'] ?? null,
                            'tlds' => json_encode($countryData['tld'] ?? []),
                            'membership' => json_encode($countryData['regionalBlocs'] ?? []),
                            'postal_code_regex' => $countryData['postalCode']['format'] ?? null,
                            'dialing_prefix' => $countryData['idd']['root'] ?? null,
                            'date_format' => 'YYYY-MM-DD',
                        ]
                    );
                    Log::channel('daily')->info('Created/Updated country: '.$country->alpha2);

                    if (! empty($countryData['currencies'])) {
                        foreach ($countryData['currencies'] as $code => $currencyData) {
                            try {
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
                                Log::channel('daily')->info("Added currency {$code} for country {$country->alpha2}");
                            } catch (\Exception $e) {
                                Log::channel('daily')->error("Error processing currency {$code} for country {$country->alpha2}: ".$e->getMessage());
                            }
                        }
                    }

                    if (! empty($countryData['languages'])) {
                        foreach ($countryData['languages'] as $code => $name) {
                            try {
                                $alpha2 = $alpha3ToAlpha2[$code] ?? $code;
                                $language = StaticLanguage::updateOrCreate(
                                    ['alpha2' => $alpha2],
                                    ['common_name' => $name]
                                );

                                $locale = $alpha2.'_'.strtolower($country->alpha2);
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
                                Log::channel('daily')->info("Added language {$code} for country {$country->alpha2}");
                            } catch (\Exception $e) {
                                Log::channel('daily')->error("Error processing language {$code} for country {$country->alpha2}: ".$e->getMessage());
                            }
                        }
                    }

                    if (! empty($countryData['timezones'])) {
                        foreach ($countryData['timezones'] as $timezoneName) {
                            try {
                                $timezone = StaticTimezone::updateOrCreate(
                                    ['name' => $timezoneName],
                                    ['offset_standard' => '', 'dst' => false]
                                );
                                StaticCountriesStaticTimezones::updateOrCreate([
                                    'country_id' => $country->id,
                                    'timezone_id' => $timezone->id,
                                ]);
                                Log::channel('daily')->info("Added timezone {$timezoneName} for country {$country->alpha2}");
                            } catch (\Exception $e) {
                                Log::channel('daily')->error("Error processing timezone {$timezoneName} for country {$country->alpha2}: ".$e->getMessage());
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::channel('daily')->error("Error processing country {$countryData['cca2']}: ".$e->getMessage());
                }
            }

            Log::channel('daily')->info('Finished importing static data from REST Countries API.');
        } catch (\Exception $e) {
            Log::channel('daily')->error('Error during import: '.$e->getMessage());
            Log::channel('daily')->error('Stack trace: '.$e->getTraceAsString());
            throw $e;
        }
    }
}
