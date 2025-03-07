<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticCountriesStaticTimezones extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_countries_static_timezones';

    protected $fillable = [
        'country_id',
        'timezone_id',
    ];

    protected $casts = [];

    public function country()
    {
        return $this->belongsTo(StaticCountry::class, 'country_id');
    }

    public function timezone()
    {
        return $this->belongsTo(StaticTimezone::class, 'timezone_id');
    }
}
