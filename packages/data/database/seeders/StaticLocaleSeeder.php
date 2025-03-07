<?php

namespace Moox\Data\Database\Seeders;

use Illuminate\Database\Seeder;

class StaticLocaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Moox\Data\Models\StaticLocale::create([
            'locale' => 'en-US',
            'name' => 'English (United States)',
            'language_id' => \Moox\Data\Models\StaticLanguage::where('alpha2', 'en')->first()->id,
            'country_id' => \Moox\Data\Models\StaticCountry::where('alpha2', 'US')->first()->id,
        ]);

        \Moox\Data\Models\StaticLocale::create([
            'locale' => 'fr-FR',
            'name' => 'French (France)',
            'language_id' => \Moox\Data\Models\StaticLanguage::where('alpha2', 'fr')->first()->id,
            'country_id' => \Moox\Data\Models\StaticCountry::where('alpha2', 'FR')->first()->id,
        ]);
    }
}
