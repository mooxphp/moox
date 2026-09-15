<?php

declare(strict_types=1);

namespace Moox\Localization\Models;

use BladeUI\Icons\Exceptions\SvgNotFound;
use BladeUI\Icons\Factory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Moox\Data\Models\StaticCountry;
use Moox\Data\Models\StaticLanguage;

/**
 * @property int $id
 * @property int $language_id
 * @property string $title
 * @property string $slug
 * @property int|null $fallback_language_id
 * @property bool $is_active_admin
 * @property bool $is_active_frontend
 * @property bool $is_default
 * @property string $fallback_behaviour
 * @property string $language_routing
 * @property string $routing_path
 * @property string $routing_subdomain
 * @property string $routing_domain
 * @property int $translation_status
 * @property bool $use_native_names
 * @property bool $show_regional_variants
 * @property bool $use_country_translations
 * @property bool $use_country_icon
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read StaticLanguage $language
 * @property-read self|null $fallbackLanguage
 */
class Localization extends Model
{
    private static ?self $cachedDefaultLocalization = null;

    private static bool $defaultLocalizationResolved = false;

    private static ?string $cachedDisplayLanguageAlpha3 = null;

    private static bool $displayLanguageAlpha3Resolved = false;

    protected $fillable = [
        'language_id',
        'title',
        'slug',
        'locale_variant',
        'fallback_language_id',
        'is_active_admin',
        'is_active_frontend',
        'is_default',
        'fallback_behaviour',
        'language_routing',
        'routing_path',
        'routing_subdomain',
        'routing_domain',
        'translation_status',
        'use_native_names',
        'show_regional_variants',
        'use_country_translations',
        'use_country_icon',
    ];

    protected $casts = [
        'is_active_admin' => 'boolean',
        'is_active_frontend' => 'boolean',
        'is_default' => 'boolean',
        'use_native_names' => 'boolean',
        'show_regional_variants' => 'boolean',
        'use_country_translations' => 'boolean',
        'use_country_icon' => 'boolean',
        'translation_status' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Localization $localization): void {
            if ($localization->is_default) {
                $localization->is_active_admin = true;
            }

            if (! $localization->show_regional_variants) {
                $localization->use_country_translations = false;
            }
        });

        static::saved(function (Localization $localization): void {
            static::clearDisplayLanguageCache();

            if (! $localization->is_default) {
                return;
            }

            static::query()
                ->where('id', '!=', $localization->id)
                ->update(['is_default' => false]);
        });
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(StaticLanguage::class, 'language_id');
    }

    public function fallbackLanguage(): BelongsTo
    {
        return $this->belongsTo(self::class, 'fallback_language_id');
    }

    /**
     * Get the locale string (e.g., de_CH, en_US)
     */
    public function getLocaleAttribute(): string
    {
        $locale = $this->attributes['locale_variant'] ?? $this->language->alpha2 ?? '';

        if (str_contains($locale, '_')) {
            $parts = explode('_', $locale, 2);
            if (count($parts) === 2) {
                $locale = $parts[0].'_'.strtoupper($parts[1]);
            }
        }

        return $locale;
    }

    /**
     * Get the display name for this localization.
     *
     * Native / regional / country-translation toggles stay per localization.
     * When country translations are on, the country label language follows the
     * admin default localization — not the content ?lang= switcher.
     */
    public function getDisplayNameAttribute(): string
    {
        $baseName = $this->use_native_names ? $this->language->native_name : $this->language->common_name;

        if (! $this->show_regional_variants) {
            return $baseName;
        }

        $locale = $this->locale;
        if (! str_contains($locale, '_')) {
            return $baseName;
        }

        $parts = explode('_', $locale, 2);
        $countryCode = strtolower($parts[1] ?? '');

        $country = StaticCountry::query()->where('alpha2', $countryCode)->first();
        if (! $country) {
            return $baseName.' ('.strtoupper($countryCode).')';
        }

        return $baseName.' ('.$this->resolveTranslatedCountryName($country).')';
    }

    /**
     * Admin default localization (request-cached).
     */
    public static function defaultLocalization(): ?self
    {
        if (self::$defaultLocalizationResolved) {
            return self::$cachedDefaultLocalization;
        }

        self::$defaultLocalizationResolved = true;
        self::$cachedDefaultLocalization = null;

        if (! Schema::hasTable('localizations')) {
            return null;
        }

        self::$cachedDefaultLocalization = static::query()
            ->where('is_default', true)
            ->with('language')
            ->first();

        return self::$cachedDefaultLocalization;
    }

    /**
     * Alpha-3 language used for translating country names in admin UI labels.
     */
    public static function displayLanguageAlpha3(): ?string
    {
        if (self::$displayLanguageAlpha3Resolved) {
            return self::$cachedDisplayLanguageAlpha3;
        }

        self::$displayLanguageAlpha3Resolved = true;
        self::$cachedDisplayLanguageAlpha3 = null;

        $alpha3 = static::defaultLocalization()?->language?->alpha3_b;
        self::$cachedDisplayLanguageAlpha3 = is_string($alpha3) && $alpha3 !== '' ? $alpha3 : null;

        return self::$cachedDisplayLanguageAlpha3;
    }

    public static function clearDisplayLanguageCache(): void
    {
        self::$defaultLocalizationResolved = false;
        self::$cachedDefaultLocalization = null;
        self::$displayLanguageAlpha3Resolved = false;
        self::$cachedDisplayLanguageAlpha3 = null;
    }

    protected function resolveTranslatedCountryName(StaticCountry $country): string
    {
        $countryName = is_string($country->common_name) ? $country->common_name : '';

        if (! $this->use_country_translations) {
            return $countryName;
        }

        $translations = $country->translations;
        if (! is_array($translations) || $translations === []) {
            return $countryName;
        }

        $alpha3 = static::displayLanguageAlpha3() ?? $this->language?->alpha3_b;
        if (! is_string($alpha3) || $alpha3 === '') {
            return $countryName;
        }

        $translation = $translations[$alpha3] ?? null;
        if (is_array($translation) && filled($translation['common'] ?? null)) {
            return (string) $translation['common'];
        }

        return $countryName;
    }

    /**
     * Get the display flag for this localization
     */
    public function getDisplayFlagAttribute(): string
    {
        return $this->resolveFlagIcon();
    }

    /**
     * Get the table flag for this localization (same logic as display_flag)
     */
    public function getTableFlagAttribute(): string
    {
        return $this->resolveFlagIcon();
    }

    /**
     * Resolve the flag icon for admin, frontend, and table views.
     */
    protected function resolveFlagIcon(): string
    {
        $languagesWithOwnFlag = ['ku', 'bo', 'eo', 'eu', 'cy', 'br', 'co', 'ar', 'aa'];

        if (in_array($this->language->alpha2, $languagesWithOwnFlag)) {
            return $this->language->flag_icon;
        }

        if ($this->use_country_icon) {
            $countryFlag = $this->resolveCountryFlagFromLocale();

            if ($countryFlag !== null) {
                return $countryFlag;
            }
        }

        return $this->language->flag_icon;
    }

    /**
     * Get the country flag from the locale variant (e.g. de_CH -> flag-ch).
     */
    protected function resolveCountryFlagFromLocale(): ?string
    {
        $locale = $this->locale;

        if (! str_contains($locale, '_')) {
            return null;
        }

        $parts = explode('_', $locale, 2);
        $countryCode = strtolower($parts[1] ?? '');

        if ($countryCode && $this->flagExists($countryCode)) {
            return 'flag-'.$countryCode;
        }

        return null;
    }

    /**
     * Check if a flag file exists using Blade Icons Factory
     */
    public function flagExists(string $flagCode): bool
    {
        try {
            $factory = app(Factory::class);
            $factory->svg('flag-'.strtolower($flagCode));

            return true;
        } catch (SvgNotFound $e) {
            return false;
        }
    }
}
