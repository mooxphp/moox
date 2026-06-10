<?php

namespace Moox\DataLegacy\Database\Seeders;

use Illuminate\Database\Seeder;
use Moox\DataLegacy\Models\StaticCountry;
use Moox\DataLegacy\Models\StaticLanguage;
use Moox\DataLegacy\Models\StaticLocale;

class StaticLocaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StaticLocale::create([
            'locale' => 'en-US',
            'name' => 'English (United States)',
            'language_id' => StaticLanguage::where('alpha2', 'en')->first()->id,
            'country_id' => StaticCountry::where('alpha2', 'US')->first()->id,
        ]);

        StaticLocale::create([
            'locale' => 'fr-FR',
            'name' => 'French (France)',
            'language_id' => StaticLanguage::where('alpha2', 'fr')->first()->id,
            'country_id' => StaticCountry::where('alpha2', 'FR')->first()->id,
        ]);
    }
}
