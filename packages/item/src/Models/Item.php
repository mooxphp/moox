<?php

declare(strict_types=1);

namespace Moox\Item\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Moox\Item\Database\Factories\ItemFactory;
use Moox\Core\Entities\Items\Item\BaseItemModel;

class Item extends BaseItemModel
{
    use HasFactory;
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

   public static function newFactory()
   {
    return ItemFactory::new();
   }
}
