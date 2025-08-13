<?php

namespace Moox\Core\Entities\Items\Draft;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

abstract class BaseDraftModel extends Model implements TranslatableContract
{
    use Translatable;

    public static function getResourceName(): string
    {
        $className = class_basename(static::class);

        return strtolower($className);
    }

    /**
     * Check if all translations are deleted and soft-delete the main model if needed
     */
    public function checkAndDeleteIfAllTranslationsDeleted(): void
    {
        $activeTranslations = $this->translations()->whereNull('deleted_at')->count();
    }
}
