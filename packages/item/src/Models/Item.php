<?php

namespace Moox\Item\Models;

use Moox\Core\Entities\Items\Item\BaseItemModel;

class Item extends BaseItemModel
{
    protected $fillable = [
        'title',
        'description',
        'custom_properties',
    ];

    protected $casts = [
        'title' => 'string',
        'custom_properties' => 'json',
    ];

    public static function getResourceName(): string
    {
        return 'item';
    }
}
