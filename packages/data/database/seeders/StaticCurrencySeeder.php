<?php

namespace Moox\Data\Database\Seeders;

use Moox\Data\Models\StaticCurrency;
use Illuminate\Database\Seeder;

class StaticCurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StaticCurrency::create([
            'code' => 'USD',
            'common_name' => 'United States Dollar',
            'symbol' => '$',
            'exonyms' => [
                'en' => 'United States Dollar',
                'fr' => 'Dollar des États-Unis',
                'es' => 'Dólar estadounidense',
            ],
        ]);

        StaticCurrency::create([
            'code' => 'EUR',
            'common_name' => 'Euro',
            'symbol' => '€',
            'exonyms' => [
                'en' => 'Euro',
                'fr' => 'Euro',
                'es' => 'Euro',
            ],
        ]);

        StaticCurrency::create([
            'code' => 'GBP',
            'common_name' => 'Pound Sterling',
            'symbol' => '£',
            'exonyms' => [
                'en' => 'Pound Sterling',
                'fr' => 'Livre sterling',
                'es' => 'Libra esterlina',
            ],
        ]);

        StaticCurrency::create([
            'code' => 'JPY',
            'common_name' => 'Japanese Yen',
            'symbol' => '¥',
            'exonyms' => [
                'en' => 'Japanese Yen',
                'fr' => 'Yen japonais',
                'es' => 'Yen japonés',
            ],
        ]);

        StaticCurrency::create([
            'code' => 'CHF',
            'common_name' => 'Swiss Franc',
            'symbol' => 'CHF',
            'exonyms' => [
                'en' => 'Swiss Franc',
                'fr' => 'Franc suisse',
                'es' => 'Franco suizo',
            ],
        ]);
    }
}
