<?php

namespace Moox\Core\Entities\Items\Item;

use Illuminate\Database\Eloquent\Model;

abstract class BaseItemModel extends Model
{
    public static function getResourceName(): string
    {
        $className = class_basename(static::class);

        return strtolower($className);
    }
}
