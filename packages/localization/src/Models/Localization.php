<?php

declare(strict_types=1);

namespace Moox\Localization\Models;

use BladeUI\Icons\Exceptions\SvgNotFound;
use BladeUI\Icons\Factory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property array $language_settings
 * @property bool $use_country_icon
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read StaticLanguage $language
 * @property-read self|null $fallbackLanguage
 */
class Localization extends Model
{
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
        'language_settings',
        'use_country_icon',
    ];

    protected $casts = [
        'is_active_admin' => 'boolean',
        'is_active_frontend' => 'boolean',
        'is_default' => 'boolean',
        'use_country_icon' => 'boolean',
        'translation_status' => 'integer',
        'language_settings' => 'array',
    ];

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
     * Get a language setting from this localization's language_settings
     */
    public function getLanguageSetting(string $key): bool
    {
        $settings = $this->language_settings ?? [];

        if (! isset($settings[$key])) {
            return config("localization.language_selector.{$key}", true);
        }

        return $settings[$key];
    }

    /**
     * Get the display name for this localization
     */
    public function getDisplayNameAttribute(): string
    {
        $useNativeNames = $this->getLanguageSetting('use_native_names');
        $showRegionalVariants = $this->getLanguageSetting('show_regional_variants');
        $useCountryTranslations = $this->getLanguageSetting('use_country_translations');

        $baseName = $useNativeNames ? $this->language->native_name : $this->language->common_name;

        if (! $showRegionalVariants) {
            return $baseName;
        }

        $locale = $this->locale;
        if (str_contains($locale, '_')) {
            $parts = explode('_', $locale, 2);
            $countryCode = strtolower($parts[1] ?? '');

            $country = StaticCountry::where('alpha2', $countryCode)->first();
            if (! $country) {
                return $baseName.' ('.strtoupper($countryCode).')';
            }

            $countryName = $country->common_name; // Default fallback

            if ($useCountryTranslations && $country->translations) {
                $currentLanguageAlpha3 = request()->get('lang') ?
                    StaticLanguage::where('alpha2', substr(request()->get('lang'), 0, 2))->first()?->alpha3_b :
                    null;

                if (! $currentLanguageAlpha3) {
                    $currentLanguageAlpha3 = $this->language->alpha3_b;
                }

                if ($currentLanguageAlpha3 && isset($country->translations[$currentLanguageAlpha3])) {
                    $translation = $country->translations[$currentLanguageAlpha3];
                    if (is_array($translation) && isset($translation['common'])) {
                        $countryName = $translation['common'];
                    }
                }
            }

            return $baseName.' ('.$countryName.')';
        }

        return $baseName;
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
            // Icons use the flag-icons-circle set prefix "flag" (e.g. flag-ch), not flag-icons-circle-ch.
            $factory->svg('flag-'.strtolower($flagCode));

            return true;
        } catch (SvgNotFound $e) {
            return false;
        }
    }
}
