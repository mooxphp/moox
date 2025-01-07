<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StaticLocaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Locale\Models\StaticLocale::create([
            'locale' => 'en-US',
            'name' => 'English (United States)',
            'language_id' => \App\Locale\Models\StaticLanguage::where('alpha2', 'en')->first()->id,
            'country_id' => \App\Locale\Models\StaticCountry::where('alpha2', 'US')->first()->id,
        ]);

        \App\Locale\Models\StaticLocale::create([
            'locale' => 'fr-FR',
            'name' => 'French (France)',
            'language_id' => \App\Locale\Models\StaticLanguage::where('alpha2', 'fr')->first()->id,
            'country_id' => \App\Locale\Models\StaticCountry::where('alpha2', 'FR')->first()->id,
        ]);
    }
}
