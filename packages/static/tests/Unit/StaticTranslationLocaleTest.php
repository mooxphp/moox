<?php

declare(strict_types=1);

use Moox\Core\Entities\Items\Static\BaseStaticModel;
use Moox\Data\Models\StaticDocumentType;
use Moox\Data\Models\StaticLanguage;
use Moox\Localization\Models\Localization;
use Moox\Static\Models\StaticEntry;
use Moox\Static\Tests\FeatureTestCase;

uses(FeatureTestCase::class);

test('resolveTranslationLocale maps locale variants to language keys', function (): void {
    expect(BaseStaticModel::resolveTranslationLocale('de_DE'))->toBe('de')
        ->and(BaseStaticModel::resolveTranslationLocale('en_US'))->toBe('en')
        ->and(BaseStaticModel::resolveTranslationLocale('de'))->toBe('de');
});

test('resolveTranslationLocale prefers localization language alpha2 when present', function (): void {
    $migrationPath = dirname(__DIR__, 3).'/data/database/migrations/create_static_languages_table.php.stub';

    if (is_file($migrationPath)) {
        $migration = include $migrationPath;
        $migration->up();
    }

    $german = StaticLanguage::query()->create([
        'alpha2' => 'de',
        'alpha3_b' => 'deu',
        'alpha3_t' => 'ger',
        'common_name' => 'German',
        'native_name' => 'Deutsch',
        'script' => 'Latin',
        'direction' => 'ltr',
        'exonyms' => [],
    ]);

    Localization::query()->create([
        'language_id' => $german->id,
        'title' => 'German (Austria)',
        'slug' => 'de-at',
        'locale_variant' => 'de_AT',
        'is_active_admin' => true,
        'is_active_frontend' => true,
        'is_default' => false,
    ]);

    expect(BaseStaticModel::resolveTranslationLocale('de_AT'))->toBe('de');
});

test('codelist translations resolve under locale variant for name display', function (): void {
    $entry = StaticEntry::query()->create(['code' => 'LOCALE-TEST']);

    $entry->translateOrNew('de')->fill(['common_name' => 'Deutscher Name']);
    $entry->translateOrNew('en')->fill(['common_name' => 'English Name']);
    $entry->save();

    expect(
        $entry->translations()->where('locale', BaseStaticModel::resolveTranslationLocale('de_DE'))->value('common_name')
    )->toBe('Deutscher Name')
        ->and(
            $entry->translations()->where('locale', BaseStaticModel::resolveTranslationLocale('en_US'))->value('common_name')
        )->toBe('English Name');
});

test('saving under a locale variant writes the language translation row', function (): void {
    $entry = StaticEntry::query()->create(['code' => 'SAVE-TEST']);

    $translationLocale = BaseStaticModel::resolveTranslationLocale('de_DE');
    $entry->translateOrNew($translationLocale)->fill(['common_name' => 'Aktualisiert']);
    $entry->save();

    expect($entry->translations()->where('locale', 'de_DE')->exists())->toBeFalse()
        ->and($entry->translations()->where('locale', 'de')->value('common_name'))->toBe('Aktualisiert');
});

test('resolveCodeByTranslation accepts language and locale variant', function (): void {
    foreach (['create_static_document_types_table', 'create_static_document_type_translations_table'] as $migration) {
        $path = dirname(__DIR__, 3).'/data/database/migrations/'.$migration.'.php.stub';

        if (is_file($path)) {
            $migrationInstance = include $path;
            $migrationInstance->up();
        }
    }

    $documentType = StaticDocumentType::query()->create([
        'code' => '381',
        'en16931_interpretation' => 'credit_note',
    ]);

    $documentType->translateOrNew('de')->fill(['common_name' => 'Gutschrift']);
    $documentType->translateOrNew('en')->fill(['common_name' => 'Credit note']);
    $documentType->save();

    expect(StaticDocumentType::resolveCodeByTranslation('Gutschrift', 'de'))->toBe('381')
        ->and(StaticDocumentType::resolveCodeByTranslation('Gutschrift', 'de_DE'))->toBe('381');
});
