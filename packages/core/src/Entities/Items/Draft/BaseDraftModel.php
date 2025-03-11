<?php

namespace Moox\Core\Entities\Items\Draft;

use Illuminate\Database\Eloquent\Model;

abstract class BaseDraftModel extends Model
{
    public static function getResourceName(): string
    {
        $className = class_basename(static::class);

        return strtolower($className);
    }
}
