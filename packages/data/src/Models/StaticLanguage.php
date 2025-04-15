<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;
use Moox\Localization\Models\Localization;

class StaticLanguage extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_languages';

    /**
     * @var array<string>
     */
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Moox\Data\Models\StaticLocale>
     */
    public function locales()
    {
        return $this->hasMany(StaticLocale::class, 'language_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Moox\Localization\Models\Localization>
     */
    public function localizations()
    {
        return $this->hasMany(Localization::class);
    }
}
