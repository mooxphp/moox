<?php

namespace Moox\Data\Jobs;

use Exception;
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
            'arc' => 'ar', // Aramaic (maps to Arabic as it's a Semitic language)
            'aym' => 'ay', // Aymara
            'aze' => 'az', // Azerbaijani
            'bel' => 'be', // Belarusian
            'ben' => 'bn', // Bengali
            'bis' => 'bi', // Bislama
            'bjz' => 'ms', // Belizean Creole (maps to Malay)
            'bos' => 'bs', // Bosnian
            'bul' => 'bg', // Bulgarian
            'cat' => 'ca', // Catalan
            'ces' => 'cs', // Czech
            'cha' => 'ch', // Chamorro
            'ckb' => 'ku', // Central Kurdish (maps to Kurdish)
            'cnr' => 'sr', // Montenegrin (maps to Serbian)
            'crs' => 'fr', // Seychellois Creole (maps to French)
            'dan' => 'da', // Danish
            'deu' => 'de', // German
            'div' => 'dv', // Divehi
            'dzo' => 'dz', // Dzongkha
            'ell' => 'el', // Greek
            'eng' => 'en', // English
            'est' => 'et', // Estonian
            'eus' => 'eu', // Basque
            'fao' => 'fo', // Faroese
            'fas' => 'fa', // Persian
            'fij' => 'fj', // Fijian
            'fin' => 'fi', // Finnish
            'fra' => 'fr', // French
            'gle' => 'ga', // Irish
            'glg' => 'gl', // Galician
            'glv' => 'gv', // Manx
            'grn' => 'gn', // Guarani
            'hat' => 'ht', // Haitian Creole
            'heb' => 'he', // Hebrew
            'her' => 'hz', // Herero
            'hgm' => 'ha', // Hai//om (maps to Hausa)
            'hif' => 'hi', // Fiji Hindi (maps to Hindi)
            'hin' => 'hi', // Hindi
            'hmo' => 'ho', // Hiri Motu
            'hrv' => 'hr', // Croatian
            'hun' => 'hu', // Hungarian
            'hye' => 'hy', // Armenian
            'ido' => 'io', // Ido
            'ina' => 'ia', // Interlingua
            'ind' => 'id', // Indonesian
            'isl' => 'is', // Icelandic
            'ita' => 'it', // Italian
            'jam' => 'en', // Jamaican Patois (maps to English)
            'jav' => 'jv', // Javanese
            'jpn' => 'ja', // Japanese
            'kal' => 'kl', // Greenlandic
            'kas' => 'ks', // Kashmiri
            'kat' => 'ka', // Georgian
            'kaz' => 'kk', // Kazakh
            'khm' => 'km', // Khmer
            'kin' => 'rw', // Kinyarwanda
            'kir' => 'ky', // Kyrgyz
            'kon' => 'kg', // Kongo
            'kor' => 'ko', // Korean
            'kua' => 'kj', // Kuanyama
            'kur' => 'ku', // Kurdish
            'lao' => 'lo', // Lao
            'lat' => 'la', // Latin
            'lav' => 'lv', // Latvian
            'lin' => 'ln', // Lingala
            'lit' => 'lt', // Lithuanian
            'ltz' => 'lb', // Luxembourgish
            'lua' => 'lu', // Luba-Katanga
            'mah' => 'mh', // Marshallese
            'mar' => 'mr', // Marathi
            'mey' => 'ms', // Hassaniyya (maps to Malay)
            'mfe' => 'fr', // Mauritian Creole (maps to French)
            'mkd' => 'mk', // Macedonian
            'mlg' => 'mg', // Malagasy
            'mlt' => 'mt', // Maltese
            'mon' => 'mn', // Mongolian
            'mri' => 'mi', // Maori
            'msa' => 'ms', // Malay
            'mya' => 'my', // Burmese
            'nau' => 'na', // Nauru
            'nbl' => 'nr', // South Ndebele
            'ndc' => 'nd', // Ndau (maps to North Ndebele)
            'nde' => 'nd', // North Ndebele
            'ndo' => 'ng', // Ndonga
            'nep' => 'ne', // Nepali
            'niu' => 'ni', // Niuean
            'nld' => 'nl', // Dutch
            'nno' => 'nn', // Norwegian Nynorsk
            'nob' => 'nb', // Norwegian Bokmål
            'nor' => 'no', // Norwegian
            'nrf' => 'fr', // Norman French (maps to French)
            'nso' => 'ns', // Northern Sotho
            'nya' => 'ny', // Chichewa
            'nzs' => 'en', // New Zealand Sign Language (maps to English)
            'oci' => 'oc', // Occitan
            'ori' => 'or', // Oriya
            'pan' => 'pa', // Punjabi
            'pli' => 'pi', // Pali
            'pol' => 'pl', // Polish
            'por' => 'pt', // Portuguese
            'pov' => 'pt', // Upper Guinea Creole (maps to Portuguese)
            'prs' => 'fa', // Dari (maps to Persian/Farsi)
            'pus' => 'ps', // Pashto
            'que' => 'qu', // Quechua
            'rar' => 'mi', // Cook Islands Maori (maps to Maori)
            'ron' => 'ro', // Romanian
            'run' => 'rn', // Rundi
            'rus' => 'ru', // Russian
            'sag' => 'sg', // Sango
            'san' => 'sa', // Sanskrit
            'sin' => 'si', // Sinhala
            'slk' => 'sk', // Slovak
            'slv' => 'sl', // Slovenian
            'smi' => 'se', // Northern Sami
            'smo' => 'sm', // Samoan
            'sna' => 'sn', // Shona
            'som' => 'so', // Somali
            'sot' => 'st', // Southern Sotho
            'spa' => 'es', // Spanish
            'sqi' => 'sq', // Albanian
            'srp' => 'sr', // Serbian
            'ssw' => 'ss', // Swati
            'sun' => 'su', // Sundanese
            'swa' => 'sw', // Swahili
            'swe' => 'sv', // Swedish
            'tah' => 'ty', // Tahitian
            'tam' => 'ta', // Tamil
            'tat' => 'tt', // Tatar
            'tel' => 'te', // Telugu (corrected from 'tet')
            'tgk' => 'tg', // Tajik
            'tha' => 'th', // Thai
            'tib' => 'bo', // Tibetan
            'tir' => 'ti', // Tigrinya
            'tkl' => 'to', // Tokelauan (maps to Tongan)
            'toi' => 'to', // Tonga (Zambia) (maps to Tongan)
            'ton' => 'to', // Tonga
            'tsn' => 'tn', // Tswana
            'tso' => 'ts', // Tsonga
            'tuk' => 'tk', // Turkmen
            'tur' => 'tr', // Turkish
            'tvl' => 'tv', // Tuvalu
            'twi' => 'tw', // Twi
            'uig' => 'ug', // Uighur
            'ukr' => 'uk', // Ukrainian
            'urd' => 'ur', // Urdu
            'uzb' => 'uz', // Uzbek
            'ven' => 've', // Venda
            'vie' => 'vi', // Vietnamese
            'vol' => 'vo', // Volapük
            'wln' => 'wa', // Walloon
            'xho' => 'xh', // Xhosa
            'yid' => 'yi', // Yiddish
            'zdj' => 'ar', // Comorian (maps to Arabic)
            'zha' => 'za', // Zhuang
            'zho' => 'zh', // Chinese
            'zul' => 'zu', // Zulu
        ];

        // Variant language codes to skip during import
        // These are regional variants/creoles that aren't needed
        $skipVariantCodes = [
            'gsw',  // Swiss German -> skip, not needed
        ];

        try {
            Log::channel('daily')->info('Attempting to fetch data from REST Countries API with multiple calls...');

            // First call: Basic country info (max 10 fields)
            $response1 = Http::timeout(60)->get('https://restcountries.com/v3.1/all', [
                'fields' => 'name,cca2,cca3,region,subregion,capital,population,area,flags,currencies',
            ]);

            if ($response1->failed()) {
                Log::channel('daily')->error('Failed to fetch basic data from REST Countries API. Status: '.$response1->status());
                Log::channel('daily')->error('Response body: '.$response1->body());

                return;
            }

            // Second call: Additional country info including translations
            $response2 = Http::timeout(60)->get('https://restcountries.com/v3.1/all', [
                'fields' => 'cca2,idd,tld,regionalBlocs,postalCode,languages,timezones,translations',
            ]);

            if ($response2->failed()) {
                Log::channel('daily')->error('Failed to fetch additional data from REST Countries API. Status: '.$response2->status());
                Log::channel('daily')->error('Response body: '.$response2->body());

                return;
            }

            $countriesBasic = $response1->json();
            $countriesAdditional = $response2->json();

            $countries = [];
            foreach ($countriesBasic as $country) {
                $cca2 = $country['cca2'] ?? null;
                if ($cca2) {
                    $additional = collect($countriesAdditional)->firstWhere('cca2', $cca2);
                    if ($additional) {
                        $countries[] = array_merge($country, $additional);
                    } else {
                        $countries[] = $country;
                    }
                }
            }

            Log::channel('daily')->info('Fetched and merged '.count($countries).' countries from REST Countries API');

            // Fetch native names from ApiCountries API
            Log::channel('daily')->info('Fetching native names from ApiCountries API...');
            $apiCountriesResponse = Http::timeout(60)->get('https://www.apicountries.com/countries');

            $nativeNamesMap = [];
            if ($apiCountriesResponse->successful()) {
                $apiCountries = $apiCountriesResponse->json();
                foreach ($apiCountries as $country) {
                    if (isset($country['alpha2Code'])) {
                        // Collect language native names
                        if (isset($country['languages'])) {
                            foreach ($country['languages'] as $lang) {
                                if (isset($lang['iso639_1']) && isset($lang['nativeName'])) {
                                    $nativeNamesMap[$lang['iso639_1']] = $lang['nativeName'];
                                }
                            }
                        }
                    }
                }
                Log::channel('daily')->info('Fetched native names for '.count($nativeNamesMap).' languages from ApiCountries API');
            } else {
                Log::channel('daily')->warning('Failed to fetch native names from ApiCountries API, continuing without them');
            }

            foreach ($countries as $countryData) {
                try {
                    Log::channel('daily')->info('Processing country: '.($countryData['cca2'] ?? 'unknown'));

                    if (! isset($countryData['cca2'])) {
                        Log::channel('daily')->warning('Skipping country - missing cca2 code');

                        continue;
                    }

                    $subregion = $countryData['subregion'] ?? null;
                    $nativeName = $countryData['name']['nativeName'] ?? [];
                    $translations = $countryData['translations'] ?? [];

                    $country = StaticCountry::updateOrCreate(
                        ['alpha2' => $countryData['cca2']],
                        [
                            'alpha3_b' => $countryData['cca3'] ?? null,
                            'common_name' => $countryData['name']['common'] ?? null,
                            'native_name' => $nativeName,
                            'exonyms' => $countryData['altSpellings'] ?? [],
                            'translations' => $translations,
                            'region' => $countryData['region'] ?? null,
                            'subregion' => $subregion,
                            'calling_code' => ! empty($countryData['idd']['root']) ? $countryData['idd']['root'] : null,
                            'capital' => $countryData['capital'] ?? [],
                            'population' => $countryData['population'] ?? null,
                            'area' => $countryData['area'] ?? null,
                            'tlds' => $countryData['tld'] ?? [],
                            'membership' => $countryData['regionalBlocs'] ?? [],
                            'postal_code_regex' => $countryData['postalCode']['format'] ?? null,
                            'dialing_prefix' => ! empty($countryData['idd']['root']) ? $countryData['idd']['root'] : null,
                            'date_format' => 'YYYY-MM-DD',
                            'embargo' => 'none',
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
                            } catch (Exception $e) {
                                Log::channel('daily')->error("Error processing currency {$code} for country {$country->alpha2}: ".$e->getMessage());
                            }
                        }
                    }

                    if (! empty($countryData['languages'])) {
                        foreach ($countryData['languages'] as $code => $name) {
                            try {
                                // Skip variant codes that aren't needed
                                if (in_array($code, $skipVariantCodes)) {
                                    Log::channel('daily')->info("Skipping variant language {$code} ({$name}) for country {$country->alpha2}");

                                    continue;
                                }

                                $alpha2 = $alpha3ToAlpha2[$code] ?? $code;
                                $nativeName = $nativeNamesMap[$alpha2] ?? $name;

                                $language = StaticLanguage::updateOrCreate(
                                    ['alpha2' => $alpha2],
                                    [
                                        'alpha3_b' => $code,
                                        'common_name' => $name,
                                        'native_name' => $nativeName,
                                    ]
                                );

                                $locale = $alpha2.'_'.strtoupper($country->alpha2);
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
                            } catch (Exception $e) {
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
                            } catch (Exception $e) {
                                Log::channel('daily')->error("Error processing timezone {$timezoneName} for country {$country->alpha2}: ".$e->getMessage());
                            }
                        }
                    }
                } catch (Exception $e) {
                    Log::channel('daily')->error("Error processing country {$countryData['cca2']}: ".$e->getMessage());
                }
            }

            Log::channel('daily')->info('Finished importing static data from REST Countries API.');
        } catch (Exception $e) {
            Log::channel('daily')->error('Error during import: '.$e->getMessage());
            Log::channel('daily')->error('Stack trace: '.$e->getTraceAsString());
            throw $e;
        }
    }
}
