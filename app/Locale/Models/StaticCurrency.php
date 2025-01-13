<?php

declare(strict_types=1);

namespace App\Locale\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticCurrency extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_currencies';

    protected $fillable = [
        'code',
        'common_name',
        'symbol',
        'exonyms',
    ];

    protected $casts = [
        'exonyms' => 'array',
    ];

    public function staticCountriesStaticCurrencies()
    {
        return $this->hasMany(StaticCountriesStaticCurrencies::class);
    }
}
