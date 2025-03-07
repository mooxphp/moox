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

    protected $casts = [
        'exonyms' => 'array',
    ];

    public function locales()
    {
        return $this->hasMany(StaticLocale::class, 'language_id');
    }

    public function localizations()
    {
        return $this->hasMany(Localization::class);
    }
}
