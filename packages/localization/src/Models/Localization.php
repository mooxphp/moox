<?php

declare(strict_types=1);

namespace Moox\Localization\Models;

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
    ];

    protected $casts = [
        'is_active_admin' => 'boolean',
        'is_active_frontend' => 'boolean',
        'is_default' => 'boolean',
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
        $locale = $this->attributes['locale_variant'] ?? $this->language?->alpha2 ?? '';

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
        $languagesWithOwnFlag = ['ku', 'bo', 'eo', 'eu', 'cy', 'br', 'co', 'ar', 'aa'];

        if (in_array($this->language->alpha2, $languagesWithOwnFlag)) {
            return $this->language->flag_icon;
        }

        $showRegionalVariants = $this->getLanguageSetting('show_regional_variants');

        if (! $showRegionalVariants) {
            return $this->language->flag_icon;
        }

        $locale = $this->locale;
        if (str_contains($locale, '_')) {
            $parts = explode('_', $locale, 2);
            $countryCode = strtolower($parts[1] ?? '');
            if ($countryCode && $this->flagExists($countryCode)) {
                return 'flag-'.$countryCode;
            }
        }


        return $this->language->flag_icon;
    }

    /**
     * Get the table flag for this localization (always shows regional variant)
     */
    public function getTableFlagAttribute(): string
    {
        $languagesWithOwnFlag = ['ku', 'bo', 'eo', 'eu', 'cy', 'br', 'co', 'ar', 'aa'];

        if (in_array($this->language->alpha2, $languagesWithOwnFlag)) {
            return $this->language->flag_icon;
        }

        $locale = $this->locale;
        if (str_contains($locale, '_')) {
            $parts = explode('_', $locale, 2);
            $countryCode = strtolower($parts[1] ?? '');
            if ($countryCode && $this->flagExists($countryCode)) {
                return 'flag-'.$countryCode;
            }
        }

        return $this->language->flag_icon;
    }

    /**
     * Check if a flag file exists using Blade Icons Factory
     */
    public function flagExists(string $flagCode): bool
    {
        try {
            $factory = app(\BladeUI\Icons\Factory::class);
            $factory->svg("flag-icons-circle-{$flagCode}");
            return true;
        } catch (\BladeUI\Icons\Exceptions\SvgNotFound $e) {
            return false;
        }
    }
}
