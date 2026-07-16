<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Model;

class FieldGroupTranslation extends Model
{
    public $timestamps = true;

    protected $table = 'builder_field_group_translations';

    protected $fillable = [
        'field_group_id',
        'locale',
        'name',
    ];
}
