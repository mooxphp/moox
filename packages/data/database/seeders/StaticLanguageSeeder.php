<?php

namespace Moox\Data\Database\Seeders;

use Illuminate\Database\Seeder;
use Moox\Data\Models\StaticLanguage;

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
            [
                'alpha2' => 'de',
                'alpha3_b' => 'ger',
                'alpha3_t' => 'deu',
                'common_name' => 'German',
                'native_name' => 'Deutsch',
                'script' => 'Latin',
                'direction' => 'ltr',
                'exonyms' => ['German', 'Allemand', 'Alemán'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'alpha2' => 'es',
                'alpha3_b' => 'spa',
                'alpha3_t' => 'spa',
                'common_name' => 'Spanish',
                'native_name' => 'Español',
                'script' => 'Latin',
                'direction' => 'ltr',
                'exonyms' => ['Spanish', 'Espagnol', 'Español'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'alpha2' => 'zh',
                'alpha3_b' => 'chi',
                'alpha3_t' => 'zho',
                'common_name' => 'Chinese',
                'native_name' => '中文',
                'script' => 'Han',
                'direction' => 'ltr',
                'exonyms' => ['Chinese', 'Chinois', 'Chino'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($languages as $language) {
            StaticLanguage::create($language);
        }
    }
}
