<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Data\Models\StaticLanguage;
use Moox\Localization\Models\Localization;
use Moox\Localization\Support\LocaleSwitcher;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config([
        'app.url' => 'https://moox.test',
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

    Schema::create('localizations', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('language_id');
        $table->string('title')->nullable();
        $table->string('slug')->nullable();
        $table->string('locale_variant')->nullable();
        $table->boolean('is_default')->default(false);
        $table->boolean('is_active_admin')->default(false);
        $table->boolean('is_active_frontend')->default(false);
        $table->boolean('use_native_names')->default(true);
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
});

afterEach(function (): void {
    Schema::dropIfExists('localizations');
    Schema::dropIfExists('static_languages');
});

it('rejects open redirects to external hosts', function (): void {
    expect(LocaleSwitcher::safeRedirectUrl('https://evil.example/phish'))->toBe('/')
        ->and(LocaleSwitcher::safeRedirectUrl('//evil.example/phish'))->toBe('/')
        ->and(LocaleSwitcher::safeRedirectUrl('javascript:alert(1)'))->toBe('/');
});

it('allows same-host absolute urls and relative paths', function (): void {
    expect(LocaleSwitcher::safeRedirectUrl('https://moox.test/admin/posts'))->toBe('https://moox.test/admin/posts')
        ->and(LocaleSwitcher::safeRedirectUrl('/admin/posts?lang=de'))->toBe('/admin/posts?lang=de')
        ->and(LocaleSwitcher::safeRedirectUrl(null))->toBe('/');
});

it('only allows language codes that are active for the given context', function (): void {
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
    $french = StaticLanguage::query()->create([
        'alpha2' => 'fr',
        'alpha3_b' => 'fra',
        'common_name' => 'French',
        'native_name' => 'Français',
    ]);

    Localization::query()->create([
        'language_id' => $german->id,
        'title' => 'DE',
        'slug' => 'de',
        'locale_variant' => 'de_DE',
        'is_active_admin' => true,
        'is_active_frontend' => true,
    ]);
    Localization::query()->create([
        'language_id' => $english->id,
        'title' => 'EN',
        'slug' => 'en',
        'locale_variant' => 'en_US',
        'is_active_admin' => true,
        'is_active_frontend' => false,
    ]);
    Localization::query()->create([
        'language_id' => $french->id,
        'title' => 'FR',
        'slug' => 'fr',
        'locale_variant' => 'fr_FR',
        'is_active_admin' => false,
        'is_active_frontend' => true,
    ]);

    expect(LocaleSwitcher::isAllowedLanguageCode('de', 'frontend'))->toBeTrue()
        ->and(LocaleSwitcher::isAllowedLanguageCode('en', 'frontend'))->toBeFalse()
        ->and(LocaleSwitcher::isAllowedLanguageCode('fr', 'frontend'))->toBeTrue()
        ->and(LocaleSwitcher::isAllowedLanguageCode('en', 'backend'))->toBeTrue()
        ->and(LocaleSwitcher::isAllowedLanguageCode('fr', 'backend'))->toBeFalse()
        ->and(LocaleSwitcher::isAllowedLanguageCode('xx', 'frontend'))->toBeFalse();
});
