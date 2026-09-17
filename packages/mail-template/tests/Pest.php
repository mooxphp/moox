<?php

declare(strict_types=1);

use Moox\Data\Models\StaticLanguage;
use Moox\Localization\Models\Localization;

if (! function_exists('ensureDefaultLocalization')) {
    function ensureDefaultLocalization(): void
    {
        $german = Localization::query()->where('is_default', true)->first();

        if ($german === null) {
            $language = StaticLanguage::query()->firstOrCreate(
                ['alpha2' => 'de'],
                [
                    'alpha3_b' => 'ger',
                    'alpha3_t' => 'deu',
                    'common_name' => 'German',
                    'native_name' => 'Deutsch',
                    'script' => 'Latin',
                    'direction' => 'ltr',
                    'exonyms' => [],
                ],
            );

            $german = Localization::query()->create([
                'language_id' => $language->id,
                'title' => 'Deutsch',
                'slug' => 'german',
                'locale_variant' => 'de',
                'fallback_language_id' => null,
                'is_active_admin' => true,
                'is_active_frontend' => true,
                'is_default' => true,
                'fallback_behaviour' => 'default',
                'language_routing' => 'path',
                'routing_path' => 'de',
                'routing_subdomain' => null,
                'routing_domain' => null,
                'translation_status' => 100,
                'use_native_names' => true,
                'show_regional_variants' => true,
                'use_country_translations' => true,
                'use_country_icon' => false,
            ]);
        }

        if (Localization::query()->whereHas('language', fn ($query) => $query->where('alpha2', 'en'))->exists()) {
            return;
        }

        $englishLanguage = StaticLanguage::query()->firstOrCreate(
            ['alpha2' => 'en'],
            [
                'alpha3_b' => 'eng',
                'alpha3_t' => 'eng',
                'common_name' => 'English',
                'native_name' => 'English',
                'script' => 'Latin',
                'direction' => 'ltr',
                'exonyms' => [],
            ],
        );

        $germanVariant = (string) $german->locale_variant;

        Localization::query()->create([
            'language_id' => $englishLanguage->id,
            'title' => 'English',
            'slug' => 'english',
            'locale_variant' => str_contains($germanVariant, '_') ? 'en_US' : 'en',
            'fallback_language_id' => $german->id,
            'is_active_admin' => true,
            'is_active_frontend' => true,
            'is_default' => false,
            'fallback_behaviour' => 'link_to_fallback',
            'language_routing' => 'path',
            'routing_path' => 'en',
            'routing_subdomain' => null,
            'routing_domain' => null,
            'translation_status' => 100,
            'use_native_names' => true,
            'show_regional_variants' => true,
            'use_country_translations' => true,
            'use_country_icon' => false,
        ]);
    }
}
