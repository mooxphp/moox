<?php

namespace Moox\Item\Models;

use Moox\Core\Entities\Items\Item\ItemModel;

class Item extends ItemModel
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
