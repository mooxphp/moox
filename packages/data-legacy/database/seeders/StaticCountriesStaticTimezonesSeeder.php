<?php

namespace Moox\DataLegacy\Database\Seeders;

use Illuminate\Database\Seeder;
use Moox\DataLegacy\Models\StaticCountry;

class StaticCountriesStaticTimezonesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $us = StaticCountry::where('alpha2', 'US')->first();
        $us->timezones()->attach([
            1, // Replace with actual timezone IDs for US
            2,
        ]);
        $us->save();

        $eu = StaticCountry::where('alpha2', 'EU')->first();
        $eu->timezones()->attach([
            3, // Replace with actual timezone ID for EU
        ]);
        $eu->save();
    }
}
