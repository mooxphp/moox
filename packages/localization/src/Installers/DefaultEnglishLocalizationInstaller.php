<?php

declare(strict_types=1);

namespace Moox\Localization\Installers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Installer\AbstractAssetInstaller;
use Moox\Data\Models\StaticLanguage;
use Moox\Localization\Models\Localization;

use function Moox\Prompts\note;

/**
 * Custom installer that ensures a default English localization exists.
 *
 * This is intended to be run via the Moox installer and will:
 * - Ensure the Data package's static_languages table exists and contains "en"
 * - Create or update the StaticLanguage "en" if missing (minimal data)
 * - Create a default Localization row for English if none exists
 */
class DefaultEnglishLocalizationInstaller extends AbstractAssetInstaller
{
    /**
     * Minimal English language data used when Data is missing "en".
     * Matches the structure expected by Moox\Data\Models\StaticLanguage.
     */
    private static function defaultEnglishLanguageAttributes(): array
    {
        return [
            'alpha2' => 'en',
            'alpha3_b' => 'eng',
            'alpha3_t' => 'eng',
            'common_name' => 'English',
            'native_name' => 'English',
            'script' => 'Latin',
            'direction' => 'ltr',
            'exonyms' => ['English', 'Anglais', 'Inglés'],
        ];
    }
    public function getType(): string
    {
        return 'localizations';
    }

    public function getLabel(): string
    {
        return 'Localizations (default English)';
    }

    public function hasItemSelection(): bool
    {
        // We always just ensure the default English localization exists,
        // there is nothing to select interactively.
        return false;
    }

    public function checkExists(string $packageName, array $items): bool
    {
        return Localization::query()
            ->whereHas('language', function ($query) {
                $query->where('alpha2', 'en');
            })
            ->exists();
    }

    public function install(array $assets): bool
    {
        // 1) Data must be present: ensure static_languages table exists
        if (! Schema::hasTable('static_languages')) {
            note('ℹ️ Table static_languages not found. Run migrations first (e.g. moox migrations installer).');

            return false;
        }

        // 2) Localization table must exist (localization package migrations)
        if (! Schema::hasTable('localizations')) {
            note('ℹ️ Table localizations not found. Run migrations first (e.g. moox migrations installer).');

            return false;
        }

        // 3) Ensure the static language "en" exists (create minimal entry if missing)
        $englishLanguage = StaticLanguage::query()
            ->where('alpha2', 'en')
            ->first();

        if (! $englishLanguage) {
            note('ℹ️ Static language "en" missing. Creating default English entry in static_languages.');
            $englishLanguage = StaticLanguage::query()->updateOrCreate(
                ['alpha2' => 'en'],
                self::defaultEnglishLanguageAttributes()
            );
        }

        // If there is already a localization for English, do nothing
        $existing = Localization::query()
            ->where('language_id', $englishLanguage->id)
            ->first();

        if ($existing) {
            // Optionally ensure there is at least one default localization
            if (! $existing->is_default && ! Localization::query()->where('is_default', true)->exists()) {
                $existing->is_default = true;
                $existing->save();
                note('✅ Existing English localization marked as default.');
            } else {
                note('ℹ️ English localization already exists. Nothing to do.');
            }

            return true;
        }

        // Wrap in transaction to be safe
        return (bool) DB::transaction(function () use ($englishLanguage) {
            $localization = Localization::query()->create([
                'language_id' => $englishLanguage->id,
                'title' => 'English',
                'slug' => 'english',
                'locale_variant' => 'en_US',
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
                'language_settings' => [
                    'locale' => 'en_US',
                ],
            ]);

            note('✅ Default English localization created ('.$localization->locale.').');

            return $localization;
        });
    }
}

