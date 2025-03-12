<?php

declare(strict_types=1);

namespace App\Builder\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;

class SimpleItem extends Model
{
    use HasModelTaxonomy;

    protected $table = 'preview_simple_items';

    protected $fillable = [
        'simple',
        'title',
        'slug',
        'content',
        'tabs',
        'taxonomy',
        'taxonomy',
        'street',
        'city',
        'postal_code',
        'country',
        'status',
        'type',
    ];

    protected $casts = [
        'slug' => 'string',
        'title' => 'string',
    ];

    protected function getResourceName(): string
    {
        return 'simple-item';
    }
}
