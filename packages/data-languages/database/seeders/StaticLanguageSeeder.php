<?php

namespace Moox\DataLanguages\Database\Seeders;

use Illuminate\Database\Seeder;
use Moox\DataLanguages\Models\StaticLanguage;

class StaticLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            [
                'alpha2' => 'en',
                'alpha3_b' => 'eng',
                'alpha3_t' => 'eng',
                'common_name' => 'English',
                'native_name' => 'English',
                'script' => 'Latin',
                'direction' => 'ltr',
                'exonyms' => ['English', 'Anglais', 'Inglés'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'alpha2' => 'fr',
                'alpha3_b' => 'fre',
                'alpha3_t' => 'fra',
                'common_name' => 'French',
                'native_name' => 'Français',
                'script' => 'Latin',
                'direction' => 'ltr',
                'exonyms' => ['French', 'Français', 'Francés'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'alpha2' => 'ar',
                'alpha3_b' => 'ara',
                'alpha3_t' => 'ara',
                'common_name' => 'Arabic',
                'native_name' => 'العربية',
                'script' => 'Arabic',
                'direction' => 'rtl',
                'exonyms' => ['Arabic', 'Arabe', 'Árabe'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($languages as $language) {
            StaticLanguage::create($language);
        }
    }
}
