<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Model;

class FieldTranslation extends Model
{
    public $timestamps = true;

    protected $table = 'builder_field_translations';

    protected $fillable = [
        'field_id',
        'locale',
        'label',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];
}
