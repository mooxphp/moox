<?php

namespace Moox\Localization\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocalizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define some example languages from your `static_languages` table
        $languages = DB::table('static_languages')->pluck('id', 'alpha2'); // Assuming static_languages has `id` and `code`

        if ($languages->isEmpty()) {
            $this->command->info('No languages found in the static_languages table. Add some before running this seeder.');

            return;
        }

        // Define localization entries with meaningful data
        $localizations = [
            [
                'title' => 'English',
                'slug' => 'english',
                'language_id' => $languages->get('en'), // Replace 'en' with the code in your `static_languages` table
                'fallback_language_id' => null,
                'is_active_admin' => true,
                'is_active_frontend' => true,
                'is_default' => true,
                'fallback_behaviour' => 'default',
                'language_routing' => 'path',
                'routing_path' => 'en',
                'routing_subdomain' => null,
                'routing_domain' => null,
                'translation_status' => 100,
                'language_settings' => json_encode(['locale' => 'en_US']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Spanish',
                'slug' => 'spanish',
                'language_id' => $languages->get('es'),
                'fallback_language_id' => null, // Fallback to English
                'is_active_admin' => true,
                'is_active_frontend' => true,
                'is_default' => false,
                'fallback_behaviour' => 'link_to_fallback',
                'language_routing' => 'path',
                'routing_path' => 'es',
                'routing_subdomain' => null,
                'routing_domain' => null,
                'translation_status' => 80,
                'language_settings' => json_encode(['locale' => 'es_ES']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'German',
                'slug' => 'german',
                'language_id' => $languages->get('de'),
                'fallback_language_id' => null, // Fallback to English
                'is_active_admin' => false,
                'is_active_frontend' => true,
                'is_default' => false,
                'fallback_behaviour' => 'translate',
                'language_routing' => 'path',
                'routing_path' => 'de',
                'routing_subdomain' => null,
                'routing_domain' => null,
                'translation_status' => 90,
                'language_settings' => json_encode(['locale' => 'de_DE']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert data into the `localizations` table
        DB::table('localizations')->insert($localizations);

        $this->command->info('Localization seeding completed.');
    }
}
