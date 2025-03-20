<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

/**
 * @property-read \Moox\Data\Models\StaticLanguage|null $language
 * @property-read \Moox\Data\Models\StaticCountry|null $country
 * @property string $flag_country_code
 */
class StaticLocale extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_locales';

    protected $fillable = [
        'language_id',
        'country_id',
        'locale',
        'flag_country_code',
        'name',
        'is_official_language',
    ];

    public function language()
    {
        return $this->belongsTo(StaticLanguage::class);
    }

    public function country()
    {
        return $this->belongsTo(StaticCountry::class);
    }

    protected $casts = [];

    protected array $languageToFlagMap = [
        'ar' => 'ar_arab',
        'en' => 'gb',
    ];

    protected array $territoryToCountryMap = [
        'sh' => 'gb',
        'um' => 'us',
        'bq' => 'nl',
    ];

    public function getLanguageFlagIconAttribute(): ?string
    {
        if (! $this->language?->alpha2) {
            return $this->getCountryFlagIconAttribute();
        }

        if (isset($this->languageToFlagMap[$this->language->alpha2])) {
            return 'flag-'.$this->languageToFlagMap[$this->language->alpha2];
        }

        $localeLanguage = strtolower(explode('_', $this->locale)[0]);

        \Log::info('Locale: '.$this->locale);
        \Log::info('Extracted language code: '.$localeLanguage);

        if (! preg_match('/^[a-z]{2}$/', $localeLanguage)) {
            \Log::warning('Invalid language code: '.$localeLanguage);

            return $this->getCountryFlagIconAttribute();
        }

        $vendorPath = public_path('vendor/flag-icons-circle/'.$localeLanguage.'.svg');
        $localPath = base_path('packages/flag-icons-circle/resources/svg/'.$localeLanguage.'.svg');

        if (file_exists($vendorPath)) {
            return 'flag-'.$localeLanguage;
        }

        if (file_exists($localPath)) {
            return 'flag-'.$localeLanguage;
        }

        return $this->getCountryFlagIconAttribute();
    }

    public function getCountryFlagIconAttribute(): ?string
    {
        if (! $this->country?->alpha2) {
            return null;
        }

        $countryCode = strtolower($this->country->alpha2);

        if (isset($this->territoryToCountryMap[$countryCode])) {
            return 'flag-'.$this->territoryToCountryMap[$countryCode];
        }

        return 'flag-'.$countryCode;
    }
}
