<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Model;

class FieldOptionTranslation extends Model
{
    public $timestamps = true;

    protected $table = 'builder_field_option_translations';

    protected $fillable = [
        'field_option_id',
        'locale',
        'label',
    ];
}
