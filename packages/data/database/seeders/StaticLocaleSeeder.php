<?php

namespace Moox\Data\Database\Seeders;

use Moox\Data\Models\StaticLocale;
use Moox\Data\Models\StaticLanguage;
use Moox\Data\Models\StaticCountry;
use Illuminate\Database\Seeder;

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
