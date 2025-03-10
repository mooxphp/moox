<?php

namespace Moox\Item\Models;

use Moox\Core\Entities\Items\Item\BaseItemModel;

class Item extends BaseItemModel
{
    protected $fillable = [
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

    // protected function getResourceName(): string
    // {
    //     return 'item';
    // }
}
