<?php

declare(strict_types=1);

namespace App\Builder\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;

class SoftDeleteItem extends Model
{
    use BaseInModel, HasModelTaxonomy, SingleSoftDeleteInModel;

    protected $table = 'preview_soft_delete_items';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'tabs',
        'taxonomy',
        'taxonomy',
        'address_section',
        'type',
        'status',
        'softDelete',
    ];

    protected $casts = [
        'slug' => 'string',
        'title' => 'string',
    ];

    protected function getResourceName(): string
    {
        return 'soft-delete-item';
    }
}
