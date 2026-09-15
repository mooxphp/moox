<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Data\Models\StaticCountry;
use Moox\Data\Models\StaticLanguage;
use Moox\Localization\Models\Localization;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'database.default' => 'localization_testing',
        'database.connections.localization_testing' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'audit.enabled' => false,
    ]);

    DB::purge('localization_testing');
    DB::setDefaultConnection('localization_testing');
    DB::reconnect('localization_testing');

    Schema::create('static_languages', function (Blueprint $table): void {
        $table->id();
        $table->string('alpha2', 2);
        $table->string('alpha3_b', 3)->nullable();
        $table->string('alpha3_t', 3)->nullable();
        $table->string('common_name');
        $table->string('native_name')->nullable();
        $table->json('exonyms')->nullable();
        $table->timestamps();
    });

    Schema::create('static_countries', function (Blueprint $table): void {
        $table->id();
        $table->string('alpha2', 2)->unique();
        $table->string('common_name');
        $table->json('translations')->nullable();
        $table->timestamps();
    });

    Schema::create('localizations', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('language_id');
        $table->string('title')->nullable();
        $table->string('slug')->nullable();
        $table->string('locale_variant')->nullable();
        $table->boolean('is_default')->default(false);
        $table->boolean('is_active_admin')->default(true);
        $table->boolean('is_active_frontend')->default(true);
        $table->boolean('use_native_names')->default(false);
        $table->boolean('show_regional_variants')->default(true);
        $table->boolean('use_country_translations')->default(true);
        $table->boolean('use_country_icon')->default(false);
        $table->string('fallback_behaviour')->nullable();
        $table->string('language_routing')->nullable();
        $table->string('routing_path')->nullable();
        $table->string('routing_subdomain')->nullable();
        $table->string('routing_domain')->nullable();
        $table->integer('translation_status')->nullable();
        $table->timestamps();
    });

    Localization::clearDisplayLanguageCache();
});

afterEach(function (): void {
    Localization::clearDisplayLanguageCache();
    Schema::dropIfExists('localizations');
    Schema::dropIfExists('static_countries');
    Schema::dropIfExists('static_languages');
});

it('translates country names using the default localization language, not the content lang switcher', function (): void {
    $german = StaticLanguage::query()->create([
        'alpha2' => 'de',
        'alpha3_b' => 'deu',
        'common_name' => 'German',
        'native_name' => 'Deutsch',
    ]);
    $english = StaticLanguage::query()->create([
        'alpha2' => 'en',
        'alpha3_b' => 'eng',
        'common_name' => 'English',
        'native_name' => 'English',
    ]);

    StaticCountry::query()->create([
        'alpha2' => 'de',
        'common_name' => 'Germany',
        'translations' => [
            'deu' => ['common' => 'Deutschland'],
            'eng' => ['common' => 'Germany'],
        ],
    ]);

    Localization::query()->create([
        'language_id' => $german->id,
        'title' => 'Deutsch',
        'slug' => 'de',
        'locale_variant' => 'de_DE',
        'is_default' => true,
        'use_native_names' => true,
        'show_regional_variants' => true,
        'use_country_translations' => true,
    ]);

    $germanLocalization = Localization::query()->create([
        'language_id' => $german->id,
        'title' => 'Deutsch DE',
        'slug' => 'de-de',
        'locale_variant' => 'de_DE',
        'is_default' => false,
        'use_native_names' => true,
        'show_regional_variants' => true,
        'use_country_translations' => true,
    ])->load('language');

    request()->merge(['lang' => 'en_US']);

    expect($germanLocalization->display_name)->toBe('Deutsch (Deutschland)');

    Localization::clearDisplayLanguageCache();
    Localization::query()->where('is_default', true)->update([
        'language_id' => $english->id,
        'locale_variant' => 'en_US',
    ]);
    Localization::clearDisplayLanguageCache();

    request()->merge(['lang' => 'de_DE']);

    expect($germanLocalization->fresh()->load('language')->display_name)->toBe('Deutsch (Germany)');
});

it('keeps per-localization toggles for native and regional display', function (): void {
    $german = StaticLanguage::query()->create([
        'alpha2' => 'de',
        'alpha3_b' => 'deu',
        'common_name' => 'German',
        'native_name' => 'Deutsch',
    ]);

    StaticCountry::query()->create([
        'alpha2' => 'de',
        'common_name' => 'Germany',
        'translations' => [
            'deu' => ['common' => 'Deutschland'],
        ],
    ]);

    Localization::query()->create([
        'language_id' => $german->id,
        'title' => 'Default',
        'slug' => 'default',
        'locale_variant' => 'de_DE',
        'is_default' => true,
        'use_native_names' => true,
        'show_regional_variants' => true,
        'use_country_translations' => true,
    ]);

    $withoutRegion = Localization::query()->create([
        'language_id' => $german->id,
        'title' => 'Plain',
        'slug' => 'plain',
        'locale_variant' => 'de_DE',
        'is_default' => false,
        'use_native_names' => false,
        'show_regional_variants' => false,
        'use_country_translations' => true,
    ])->load('language');

    expect($withoutRegion->display_name)->toBe('German');
});
