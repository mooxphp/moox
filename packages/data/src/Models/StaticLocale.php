<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

/**
 * @property-read StaticLanguage|null $language
 * @property-read StaticCountry|null $country
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

    protected array $territoryToCountryMap = [
        'sh' => 'gb',
        'um' => 'us',
        'bq' => 'nl',
        'hm' => 'au',
        'aq' => 'gb',
        'bv' => 'no',
        'gs' => 'gb',
        'io' => 'gb',
        'pn' => 'gb',
        'sj' => 'no',
        'tf' => 'fr',
    ];

    public function getLanguageFlagIconAttribute(): ?string
    {
        if (! $this->language?->alpha2) {
            return $this->getCountryFlagIconAttribute();
        }

        // Use the flag_icon from the StaticLanguage model
        return $this->language->flag_icon;
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
