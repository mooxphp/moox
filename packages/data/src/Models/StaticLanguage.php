<?php

declare(strict_types=1);

namespace Moox\Data\Models;

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
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Moox\Data\Models\StaticLocale> $locales
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Moox\Localization\Models\Localization> $localizations
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
}
