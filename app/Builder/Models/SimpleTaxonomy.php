<?php

declare(strict_types=1);

namespace App\Builder\Models;

use Illuminate\Database\Eloquent\Model;

class SimpleTaxonomy extends Model
{
    protected $table = 'preview_simple_taxonomies';

    protected $fillable = [
        'title',
        'slug',
        'description',
    ];

    protected $casts = [
    ];
}
