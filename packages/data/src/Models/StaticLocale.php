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
        'mi' => 'nz',  // Māori -> New Zealand
        'ar' => 'ar_arab',
    ];

    public function getLanguageFlagIconAttribute(): ?string
    {
        if ($this->language?->alpha2) {
            $flagCode = $this->languageToFlagMap[$this->language->alpha2] ?? strtolower($this->language->alpha2);

            return 'flag-'.$flagCode;
        }

        if (! $this->country?->alpha2) {
            return null;
        }

        return 'flag-'.strtolower($this->country->alpha2);
    }

    public function getCountryFlagIconAttribute(): ?string
    {
        if (! $this->country?->alpha2) {
            return null;
        }

        return 'flag-'.strtolower($this->country->alpha2);
    }
}
