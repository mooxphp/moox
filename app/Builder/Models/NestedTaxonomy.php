<?php

declare(strict_types=1);

namespace App\Builder\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

class NestedTaxonomy extends Model
{
    use BaseInModel;
    use SingleSimpleInModel;
    protected $table = 'preview_nested_taxonomies';

    protected $fillable = [
        'simple',
        'title',
        'slug',
        'description',
    ];

    protected $casts = [
    ];
}
