<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticCountriesStaticCurrencies extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_countries_static_currencies';

    protected $fillable = [
        'country_id',
        'currency_id',
        'is_primary',
    ];

    protected $casts = [];

    public function country()
    {
        return $this->belongsTo(StaticCountry::class);
    }

    public function currency()
    {
        return $this->belongsTo(StaticCurrency::class);
    }
}
