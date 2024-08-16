<?php

namespace Moox\Locate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'area_type',
        'description',
        'nutrition',
        'tropical',
    ];

    protected $casts = [
        'nutrition' => 'array',
        'tropical' => 'boolean',
    ];
}
