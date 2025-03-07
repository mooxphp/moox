<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticTimezone extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_timezones';

    protected $fillable = [
        'name',
        'offset_standard',
        'dst',
        'dst_start',
        'dst_end',
    ];

    protected $casts = [];

    public function countries()
    {
        return $this->belongsToMany(StaticCountry::class, 'static_countries_static_timezones', 'timezone_id', 'country_id');
    }
}
