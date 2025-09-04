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
            'cs' => 'cz', // Czech -> Czech Republic
            'ta' => 'in', // Tamil -> India
        ];

        $code = strtolower($this->alpha2);

        return 'flag-'.($languageToFlagMap[$code] ?? $code);
    }
}
