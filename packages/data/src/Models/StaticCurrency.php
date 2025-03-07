<?php

declare(strict_types=1);

namespace Moox\Data\Models;

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

    public function countries()
    {
        return $this->belongsToMany(StaticCountry::class, 'static_countries_static_currencies', 'currency_id', 'country_id');
    }
}
