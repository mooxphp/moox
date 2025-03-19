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
        'nzs' => 'nz',  // New Zealand Sign Language -> New Zealand
        'he' => 'il',  // Hebrew -> Israel
        'en' => 'gb',  // English -> United Kingdom
        'fr' => 'fr',  // French -> France
        'de' => 'de',  // German -> Germany
        'es' => 'es',  // Spanish -> Spain
        'it' => 'it',  // Italian -> Italy
        'nl' => 'nl',  // Dutch -> Netherlands
        'pt' => 'pt',  // Portuguese -> Portugal
        'ru' => 'ru',  // Russian -> Russia
        'zh' => 'cn',  // Chinese -> China
        'ja' => 'jp',  // Japanese -> Japan
        'ko' => 'kr',  // Korean -> South Korea
        'hi' => 'in',  // Hindi -> India
        'bn' => 'bd',  // Bengali -> Bangladesh
        'ur' => 'pk',  // Urdu -> Pakistan
        'fa' => 'ir',  // Persian -> Iran
        'tr' => 'tr',  // Turkish -> Turkey
        'el' => 'gr',  // Greek -> Greece
        'pl' => 'pl',  // Polish -> Poland
        'uk' => 'ua',  // Ukrainian -> Ukraine
        'ro' => 'ro',  // Romanian -> Romania
        'hu' => 'hu',  // Hungarian -> Hungary
        'cs' => 'cz',  // Czech -> Czech Republic
        'sv' => 'se',  // Swedish -> Sweden
        'da' => 'dk',  // Danish -> Denmark
        'fi' => 'fi',  // Finnish -> Finland
        'no' => 'no',  // Norwegian -> Norway
        'sk' => 'sk',  // Slovak -> Slovakia
        'hr' => 'hr',  // Croatian -> Croatia
        'ca' => 'es',  // Catalan -> Spain
        'vi' => 'vn',  // Vietnamese -> Vietnam
        'th' => 'th',  // Thai -> Thailand
        'id' => 'id',  // Indonesian -> Indonesia
        'ms' => 'my',  // Malay -> Malaysia
        'fil' => 'ph',  // Filipino -> Philippines
        'ne' => 'np',  // Nepali -> Nepal
        'si' => 'lk',  // Sinhala -> Sri Lanka
        'km' => 'kh',  // Khmer -> Cambodia
        'my' => 'mm',  // Burmese -> Myanmar
        'ka' => 'ge',  // Georgian -> Georgia
        'am' => 'et',  // Amharic -> Ethiopia
        'sw' => 'tz',  // Swahili -> Tanzania
        'zu' => 'za',  // Zulu -> South Africa
        'af' => 'za',  // Afrikaans -> South Africa
        'xh' => 'za',  // Xhosa -> South Africa
        'ta' => 'in',  // Tamil -> India
        'te' => 'in',  // Telugu -> India
        'mr' => 'in',  // Marathi -> India
        'gu' => 'in',  // Gujarati -> India
        'kn' => 'in',  // Kannada -> India
        'ml' => 'in',  // Malayalam -> India
        'pa' => 'in',  // Punjabi -> India
        'as' => 'in',  // Assamese -> India
        'or' => 'in',  // Odia -> India
        'sa' => 'in',  // Sanskrit -> India
        'ln' => 'cd',  // Lingala -> Democratic Republic of Congo
        'kg' => 'cd',  // Kongo -> Democratic Republic of Congo
        'sm' => 'ws',  // Samoan -> Samoa
        'mn' => 'mn',  // Mongolian -> Mongolia
    ];

    public function getLanguageFlagIconAttribute(): ?string
    {
        if ($this->language?->alpha2) {
            $flagCode = $this->languageToFlagMap[$this->language->alpha2] ?? null;

            if (! $flagCode) {
                return null;
            }

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
