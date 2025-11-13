<?php

namespace Moox\Data\Database\Seeders;

use Illuminate\Database\Seeder;
use Moox\Data\Models\StaticCountry;

class StaticCountriesStaticCurrenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $us = StaticCountry::where('alpha2', 'US')->first();
        $us->currencies()->attach([
            1, // USD currency ID
        ]);
        $us->save();

        $eu = StaticCountry::where('alpha2', 'FR')->first();
        $eu->currencies()->attach([
            2, // EUR currency ID
        ]);
        $eu->save();
    }
}
