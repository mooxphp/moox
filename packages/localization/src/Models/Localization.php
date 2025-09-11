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
     * Get the display name for this localization
     */
    public function getDisplayNameAttribute(): string
    {
        $useNativeNames = config('localization.language_selector.use_native_names', true);
        $showRegionalVariants = config('localization.language_selector.show_regional_variants', true);
        $useCountryTranslations = config('localization.language_selector.use_country_translations', true);

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

            if ($useNativeNames && $useCountryTranslations && $country->translations) {
                $currentLanguage = $this->language->alpha2;
                if (isset($country->translations[$currentLanguage])) {
                    $countryName = $country->translations[$currentLanguage];
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
        $showRegionalVariants = config('localization.language_selector.show_regional_variants', true);

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
     * Check if a flag file exists
     */
    public function flagExists(string $flagCode): bool
    {
        $packagePath = base_path('packages/flag-icons-circle/resources/svg/'.$flagCode.'.svg');
        if (file_exists($packagePath)) {
            return true;
        }

        $publicPath = public_path('vendor/flag-icons-circle/'.$flagCode.'.svg');

        return file_exists($publicPath);
    }

    /**
     * Get country flag for the language
     */
    private function getCountryFlag(): string
    {
        // Extended mapping for languages to their primary country
        $languageToCountry = [
            'de' => 'de',
            'en' => 'gb',
            'fr' => 'fr',
            'es' => 'es',
            'it' => 'it',
            'pt' => 'pt',
            'ru' => 'ru',
            'zh' => 'cn',
            'ja' => 'jp',
            'ko' => 'kr',
            'ar' => 'sa',
            'cs' => 'cz', // Czech -> Czech Republic
            'sk' => 'sk', // Slovak -> Slovakia
            'sl' => 'si', // Slovenian -> Slovenia
            'hr' => 'hr', // Croatian -> Croatia
            'sr' => 'rs', // Serbian -> Serbia
            'bs' => 'ba', // Bosnian -> Bosnia and Herzegovina
            'mk' => 'mk', // Macedonian -> North Macedonia
            'bg' => 'bg', // Bulgarian -> Bulgaria
            'ro' => 'ro', // Romanian -> Romania
            'hu' => 'hu', // Hungarian -> Hungary
            'pl' => 'pl', // Polish -> Poland
            'ca' => 'es', // Catalan -> Spain
            'eu' => 'es', // Basque -> Spain
            'gl' => 'es', // Galician -> Spain
            'cy' => 'gb', // Welsh -> United Kingdom
            'br' => 'fr', // Breton -> France
            'co' => 'fr', // Corsican -> France
            'ku' => 'iq', // Kurdish -> Iraq
            'bo' => 'cn', // Tibetan -> China
            'yue' => 'cn', // Cantonese -> China
            'nan' => 'tw', // Hokkien -> Taiwan
            'hak' => 'cn', // Hakka -> China
            'wuu' => 'cn', // Wu -> China
            'tzm' => 'ma', // Berber -> Morocco
            'ber' => 'ma', // Berber -> Morocco
        ];

        $countryCode = $languageToCountry[$this->language->alpha2] ?? $this->language->alpha2;

        if ($this->flagExists($countryCode)) {
            return 'flag-'.$countryCode;
        }

        // Last resort: return original language flag
        return $this->language->flag_icon;
    }
}
