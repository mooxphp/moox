<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StaticCurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Moox\DataLanguages\Models\StaticCurrency::create([
            'code' => 'USD',
            'common_name' => 'United States Dollar',
            'symbol' => '$',
            'exonyms' => json_encode([
                'en' => 'United States Dollar',
                'fr' => 'Dollar des États-Unis',
                'es' => 'Dólar estadounidense',
            ], JSON_UNESCAPED_UNICODE),
        ]);

        \Moox\DataLanguages\Models\StaticCurrency::create([
            'code' => 'EUR',
            'common_name' => 'Euro',
            'symbol' => '€',
            'exonyms' => json_encode([
                'en' => 'Euro',
                'fr' => 'Euro',
                'es' => 'Euro',
            ], JSON_UNESCAPED_UNICODE),
        ]);

        \Moox\DataLanguages\Models\StaticCurrency::create([
            'code' => 'GBP',
            'common_name' => 'Pound Sterling',
            'symbol' => '£',
            'exonyms' => json_encode([
                'en' => 'Pound Sterling',
                'fr' => 'Livre sterling',
                'es' => 'Libra esterlina',
            ], JSON_UNESCAPED_UNICODE),
        ]);

        \Moox\DataLanguages\Models\StaticCurrency::create([
            'code' => 'JPY',
            'common_name' => 'Japanese Yen',
            'symbol' => '¥',
            'exonyms' => json_encode([
                'en' => 'Japanese Yen',
                'fr' => 'Yen japonais',
                'es' => 'Yen japonés',
            ], JSON_UNESCAPED_SLASHES),
        ]);

        \Moox\DataLanguages\Models\StaticCurrency::create([
            'code' => 'CHF',
            'common_name' => 'Swiss Franc',
            'symbol' => 'CHF',
            'exonyms' => json_encode([
                'en' => 'Swiss Franc',
                'fr' => 'Franc suisse',
                'es' => 'Franco suizo',
            ]),
        ]);
    }
}
