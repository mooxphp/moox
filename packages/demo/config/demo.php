<?php

return [

    'dataset_sizes' => [
        'small' => 100,
        'medium' => 1_000,
        'large' => 10_000,
        'huge' => 100_000,
    ],

    'default_dataset' => 'small',

    'default_language_count' => 3,

    'default_locales' => [
        'de_DE',
        'en_US',
        'es_ES',
    ],

    /**
     * Always created by DemoLocalizationStep (merged with --locales / default_locales).
     * Required by CategorySeeder and other seeders with fixed locale lists.
     */
    'ensure_locales' => [
        'cs_CZ',
        'en_US',
        'de_DE',
        'pl_PL',
    ],

    /**
     * Explicit package slug order before topological sort (earlier = higher priority).
     */
    'seeder_order' => [
        'data',
        'localization',
        'media',
        'user',
        'attribute',
        'tag',
        'category',
        'draft',
        'product',
        'item',
        'news',
        'page',
        'press',
    ],

    /**
     * Slugs to skip when running package entry seeders.
     */
    'seeder_skip' => [
        'demo',
        'core',
        'devlink',
        'devtools',
        'skeleton',
        'build',
        'monorepo',
    ],

    /**
     * Basenames of seeders to never run directly (called by a parent seeder).
     */
    'nested_seeder_basenames' => [
        'StaticCountrySeeder',
        'StaticLanguageSeeder',
        'StaticCurrencySeeder',
        'StaticTimezoneSeeder',
        'StaticLocaleSeeder',
        'StaticCountriesStaticTimezonesSeeder',
        'StaticCountriesStaticCurrenciesSeeder',
    ],

    'demo_user' => [
        'enabled' => true,
        'name' => 'Moox Demo',
        'email' => 'demo@moox.org',
        'password' => 'password',
    ],

    'media' => [
        'disk' => 'public',
        'directory' => 'demo',
        'collection' => 'default',
        /** Resolved to assets/images/users when null (see DemoRunner). */
        'users_path' => null,
        /**
         * Static demo asset pools (under packages/demo/resources/demo/assets/).
         * Used by seeders once DemoAssetCatalog / import step is wired (Phase 2.5).
         */
        'assets_path' => null,
        'products_path' => null,
        'pdf_path' => null,
        'documents_path' => null,
        'audio_path' => null,
        'videos_path' => null,
    ],

];
