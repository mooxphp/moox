<?php

declare(strict_types=1);

namespace App\Locale\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class StaticCountry extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_countries';

    protected $fillable = [
        'simple',
        'alpha2',
        'alpha3_b',
        'alpha3_t',
        'common_name',
        'native_name',
        'exonyms',
        'region',
        'subregion',
        'calling_code',
        'capital',
        'population',
        'area',
        'links',
        'tlds',
        'membership',
        'embargo',
        'embargo_data',
        'address_format',
        'postal_code_regex',
        'dialing_prefix',
        'phone_number_formatting',
        'date_format',
        'currency_format',
    ];

    protected $casts = [
        'exonyms' => 'array',
        'links' => 'array',
        'tlds' => 'array',
        'membership' => 'array',
        'embargo_data' => 'array',
        'address_format' => 'array',
        'phone_number_formatting' => 'array',
        'currency_format' => 'array',
    ];

    public function locales()
    {
        return $this->hasMany(StaticLocale::class);
    }
}
