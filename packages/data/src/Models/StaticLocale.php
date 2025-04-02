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
        'ar' => 'sa', // Arabic -> Saudi Arabia
        'en' => 'gb', // English -> United Kingdom
        'es' => 'es', // Spanish -> Spain
        'fr' => 'fr', // French -> France
        'pt' => 'pt', // Portuguese -> Portugal
        'ru' => 'ru', // Russian -> Russia
        'zh' => 'cn', // Chinese -> China
        'de' => 'de', // German -> Germany
        'hi' => 'in', // Hindi -> India
        'ja' => 'jp', // Japanese -> Japan
        'ko' => 'kr', // Korean -> South Korea
        'fa' => 'ir', // Persian -> Iran
        'tr' => 'tr', // Turkish -> Turkey
        'it' => 'it', // Italian -> Italy
        'pl' => 'pl', // Polish -> Poland
        'uk' => 'ua', // Ukrainian -> Ukraine
        'vi' => 'vn', // Vietnamese -> Vietnam
        'th' => 'th', // Thai -> Thailand
        'nl' => 'nl', // Dutch -> Netherlands
        'el' => 'gr', // Greek -> Greece
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

        if (! $this->is_official_language && isset($this->languageToFlagMap[$this->language->alpha2])) {
            return 'flag-'.$this->languageToFlagMap[$this->language->alpha2];
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
