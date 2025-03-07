<?php

namespace Moox\Data\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(StaticCountrySeeder::class);
        $this->call(StaticLanguageSeeder::class);
        $this->call(StaticCurrencySeeder::class);
        $this->call(StaticTimezoneSeeder::class);
        $this->call(StaticLocaleSeeder::class);
        // $this->call(StaticCountriesStaticTimezonesSeeder::class);
        // $this->call(StaticCountriesStaticCurrenciesSeeder::class);
    }
}
