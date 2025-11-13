<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;
use Moox\Localization\Models\Localization;

/**
 * @property int $id
 * @property string $alpha2
 * @property string $alpha3_b
 * @property string $alpha3_t
 * @property string $common_name
 * @property string $native_name
 * @property string|null $script
 * @property string|null $direction
 * @property array $exonyms
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, StaticLocale> $locales
 * @property-read Collection<int, Localization> $localizations
 */
class StaticLanguage extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_languages';

    protected $fillable = [
        'alpha2',
        'alpha3_b',
        'alpha3_t',
        'common_name',
        'native_name',
        'script',
        'direction',
        'exonyms',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'exonyms' => 'array',
    ];

    public function locales(): HasMany
    {
        return $this->hasMany(StaticLocale::class, 'language_id');
    }

    public function localizations(): HasMany
    {
        return $this->hasMany(Localization::class);
    }

    /**
     * Returns the appropriate flag for the language
     * Uses the languageToFlagMap for consistent flags
     */
    public function getFlagIconAttribute(): string
    {
        $languageToFlagMap = [
            'ar' => 'ar_arab', // Arabic -> Arabic flag (special case)
            'en' => 'gb', // English -> United Kingdom
            'es' => 'es', // Spanish -> Spain
            'fr' => 'fr', // French -> France
            'it' => 'it', // Italian -> Italy
            'pt' => 'pt', // Portuguese -> Portugal
            'ru' => 'ru', // Russian -> Russia
            'pl' => 'pl', // Polish -> Poland
            'zh' => 'cn', // Chinese -> China
            'de' => 'de', // German -> Germany
            'hi' => 'in', // Hindi -> India
            'ja' => 'jp', // Japanese -> Japan
            'ko' => 'kr', // Korean -> South Korea
            'tr' => 'tr', // Turkish -> Turkey
            'fa' => 'ir', // Persian -> Iran
            'uk' => 'ua', // Ukrainian -> Ukraine
            'vi' => 'vn', // Vietnamese -> Vietnam
            'th' => 'th', // Thai -> Thailand
            'nl' => 'nl', // Dutch -> Netherlands
            'el' => 'gr', // Greek -> Greece
            'cs' => 'cz', // Czech -> Czech Republic
            'ta' => 'in', // Tamil -> India
            'sw' => 'tz', // Swahili -> Tanzania
            'kk' => 'kz', // Kazakh -> Kazakhstan
            'rn' => 'bi', // Rundi -> Burundi
            'sq' => 'al', // Albanian -> Albania
            'tpi' => 'pg', // Tok Pisin -> Papua New Guinea
            'ho' => 'pg', // Hiri Motu -> Papua New Guinea
            'pap' => 'aw', // Papiamento -> Aruba
            'glc' => 'es', // Galician -> Spain
            'pih' => 'gb', // Pitkern -> United Kingdom
            'ch' => 'gu', // Chamorro -> Guam
            'ln' => 'cd', // Lingala -> Democratic Republic of Congo
            'roh' => 'ch', // Romansh -> Switzerland
            'ka' => 'ge', // Georgian -> Georgia
            'mi' => 'nz', // Maori -> New Zealand
            'gv' => 'im', // Manx -> Isle of Man
            'ny' => 'mw', // Chichewa -> Malawi
            'zu' => 'za', // Zulu -> South Africa
            'xh' => 'za', // Xhosa -> South Africa
            'ts' => 'za', // Tsonga -> South Africa
            'ns' => 'za', // Northern Sotho -> South Africa
            'nb' => 'no', // Norwegian Bokmål -> Norway
            'nn' => 'no', // Norwegian Nynorsk -> Norway
            'qu' => 'pe', // Quechua -> Peru
            'ay' => 'bo', // Aymara -> Bolivia
            'tet' => 'tl', // Tetum -> East Timor
            'da' => 'dk', // Danish -> Denmark
            'cal' => 'mp', // Carolinian -> Northern Mariana Islands
            'he' => 'il', // Hebrew -> Israel
            'gil' => 'ki', // Gilbertese -> Kiribati
            'ti' => 'er', // Tigrinya -> Eritrea
            'nfr' => 'nf', // Norfuk -> Norfolk Island
            'loz' => 'zm', // Lozi -> Zambia
            'kwn' => 'na', // Kwangali -> Namibia
            'ha' => 'na', // Hausa -> Namibia
            'hz' => 'na', // Herero -> Namibia
            'dv' => 'mv', // Dhivehi -> Maldives
            'ber' => 'ma', // Berber -> Morocco
            'kl' => 'gl', // Greenlandic -> Greenland
            'pau' => 'pw', // Palauan -> Palau
            'hy' => 'am', // Armenian -> Armenia
            'ur' => 'pk', // Urdu -> Pakistan
            'tn' => 'bw', // Tswana -> Botswana
            'sr' => 'rs', // Serbian -> Serbia
            'et' => 'ee', // Estonian -> Estonia
            'bs' => 'ba', // Bosnian -> Bosnia and Herzegovina
            'fil' => 'ph', // Filipino -> Philippines
            'lb' => 'lu', // Luxembourgish -> Luxembourg
            'st' => 'ls', // Southern Sotho -> Lesotho
            'lo' => 'la', // Lao -> Laos
            'zib' => 'zw', // Zimbabwe Sign Language -> Zimbabwe
            'tg' => 'tj', // Tajik -> Tajikistan
            'nd' => 'zw', // Northern Ndebele -> Zimbabwe
            'khi' => 'na', // Khoekhoe -> Namibia
            'sn' => 'zw', // Shona -> Zimbabwe
            've' => 'za', // Venda -> South Africa
            'kck' => 'na', // Khoekhoe -> Namibia
            'bwg' => 'zw', // Chibarwe -> Zimbabwe
            'dz' => 'bt', // Dzongkha -> Bhutan
            'gn' => 'py', // Guarani -> Paraguay
            'be' => 'by', // Belarusian -> Belarus
            'ps' => 'af', // Pashto -> Afghanistan
            'ky' => 'kg', // Kyrgyz -> Kyrgyzstan
            'kg' => 'cd', // Kongo -> Democratic Republic of Congo
            'se' => 'no', // Northern Sami -> Norway
            'sv' => 'se', // Swedish -> Sweden
            'sl' => 'si', // Slovenian -> Slovenia
            'ng' => 'na', // Ndonga -> Namibia
            'ga' => 'ie', // Irish -> Ireland
            'aa' => 'afar', // Afar -> Afar flag (special case)
            'ff' => 'gn', // Fula -> Guinea
            'ca' => 'ad', // Catalan -> Andorra (Estelada alternative)
            'eu' => 'eu', // Basque -> Basque Country (Ikurriña)
            'gl' => 'gl', // Galician -> Galicia
            'ku' => 'kurdistan', // Kurdish -> Kurdistan flag
            'bo' => 'tibet', // Tibetan -> Tibet flag
            'eo' => 'eo', // Esperanto -> Esperanto flag (fallback to eo if not available)
            'yue' => 'hk', // Cantonese -> Hong Kong
            'nan' => 'tw', // Hokkien -> Taiwan
            'tzm' => 'ma', // Berber/Tamazight -> Morocco (Amazigh flag fallback)
            'cy' => 'cy', // Welsh -> Wales flag
            'br' => 'fr-bre', // Breton -> Brittany flag (Gwenn-ha-du)
            'co' => 'co', // Corsican -> Corsica flag
            'rom' => 'rom', // Romanes -> Roma flag (MISSING - using code as fallback)
            'yi' => 'yi', // Yiddish -> Yiddish flag (MISSING - using code as fallback)
            'hak' => 'cn', // Hakka -> China flag (MISSING - using China as fallback)
            'wuu' => 'cn', // Wu -> China flag (MISSING - using China as fallback)
        ];

        $code = strtolower($this->alpha2);

        return 'flag-'.($languageToFlagMap[$code] ?? $code);
    }

    /**
     * Get the display name for the language based on current locale
     */
    public function getDisplayNameAttribute(): string
    {
        $useNativeNames = config('localization.language_selector.use_native_names', true);

        if (! $useNativeNames) {
            return $this->common_name;
        }

        if (is_string($this->native_name) && ! empty($this->native_name)) {
            return $this->native_name;
        }

        return $this->common_name;
    }
}
