<?php

namespace Moox\Data\Database\Seeders;

use Illuminate\Database\Seeder;

class StaticCountriesStaticTimezonesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $us = \Moox\Data\Models\StaticCountry::where('alpha2', 'US')->first();
        $us->timezones()->attach([
            1, // Replace with actual timezone IDs for US
            2,
        ]);
        $us->save();

        $eu = \Moox\Data\Models\StaticCountry::where('alpha2', 'EU')->first();
        $eu->timezones()->attach([
            3, // Replace with actual timezone ID for EU
        ]);
        $eu->save();
    }
}
