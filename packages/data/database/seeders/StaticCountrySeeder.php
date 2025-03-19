<?php

namespace Moox\Data\Database\Seeders;

use Illuminate\Database\Seeder;
use Moox\Data\Models\StaticCountry;

class StaticCountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            [
                'alpha2' => 'US',
                'alpha3_b' => 'USA',
                'alpha3_t' => '840',
                'common_name' => 'United States',
                'native_name' => 'United States',
                'exonyms' => ['USA', 'America', 'Estados Unidos'],
                'region' => 'Americas',
                'subregion' => 'North America',
                'calling_code' => '+1',
                'capital' => 'Washington, D.C.',
                'population' => 331000000,
                'area' => 9833517,
                'links' => ['https://www.usa.gov'],
                'tlds' => ['.us'],
                'membership' => ['UN', 'NATO'],
                'embargo' => false,
                'embargo_data' => [],
                'address_format' => [
                    'line1' => '{street_address}',
                    'line2' => '{city}, {state} {postal_code}',
                    'line3' => '{country}',
                ],
                'postal_code_regex' => '/^\d{5}(-\d{4})?$/',
                'dialing_prefix' => '+1',
                'phone_number_formatting' => [
                    'format' => '(XXX) XXX-XXXX',
                    'example' => '(123) 456-7890',
                ],
                'date_format' => 'm/d/Y',
                'currency_format' => [
                    'symbol' => '$',
                    'code' => 'USD',
                    'format' => '{symbol}{amount}',
                ],
            ],
            [
                'alpha2' => 'FR',
                'alpha3_b' => 'FRA',
                'alpha3_t' => '250',
                'common_name' => 'France',
                'native_name' => 'France',
                'exonyms' => ['France', 'Francia', 'Frankreich'],
                'region' => 'Europe',
                'subregion' => 'Western Europe',
                'calling_code' => '+33',
                'capital' => 'Paris',
                'population' => 67081000,
                'area' => 551695,
                'links' => ['https://www.france.fr'],
                'tlds' => ['.fr'],
                'membership' => ['UN', 'EU', 'NATO'],
                'embargo' => false,
                'embargo_data' => [],
                'address_format' => [
                    'line1' => '{street_address}',
                    'line2' => '{postal_code} {city}',
                    'line3' => '{country}',
                ],
                'postal_code_regex' => '/^\d{5}$/',
                'dialing_prefix' => '+33',
                'phone_number_formatting' => [
                    'format' => '0X XX XX XX XX',
                    'example' => '01 23 45 67 89',
                ],
                'date_format' => 'd/m/Y',
                'currency_format' => [
                    'symbol' => '€',
                    'code' => 'EUR',
                    'format' => '{amount} {symbol}',
                ],
            ],
        ];

        foreach ($countries as $country) {
            StaticCountry::create($country);
        }
    }
}
