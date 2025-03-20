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

    public function getLanguageFlagIconAttribute(): ?string
    {
        if (! $this->language?->alpha2) {
            return $this->getCountryFlagIconAttribute();
        }

        if (isset($this->languageToFlagMap[$this->language->alpha2])) {
            return 'flag-'.$this->languageToFlagMap[$this->language->alpha2];
        }

        $localeLanguage = strtolower(explode('_', $this->locale)[0]);
        $vendorPath = resource_path('vendor/flag-icons-circle/resources/svg/'.$localeLanguage.'.svg');
        $localPath = base_path('packages/flag-icons-circle/resources/svg/'.$localeLanguage.'.svg');

        \Log::info('Checking vendor path: '.$vendorPath);
        \Log::info('Checking local path: '.$localPath);

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

        return 'flag-'.strtolower($this->country->alpha2);
    }
}
