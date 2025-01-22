<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Taxonomy\TaxonomyInModel;

class SimpleItem extends Model
{
    use HasFactory;
    use TaxonomyInModel;
    protected $table = 'simple_items';

    protected function getResourceName(): string
    {
        return 'simple_item';
    }

    protected $fillable = [
        'title',
        'slug',
        'content',
        'featured_image_url',
    ];
}
