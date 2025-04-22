<?php

namespace Moox\Core\Entities\Items\Draft;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;

abstract class BaseDraftTranslationModel extends Model 
{
    use SoftDeletes;

    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            'to_publish_at' => 'datetime',
            'published_at' => 'datetime',
            'to_unpublish_at' => 'datetime',
            'unpublished_at' => 'datetime',
            'restored_at' => 'datetime'
        ], $this->getCustomCasts());
    }

    /**
     * Get custom casts for child models to extend.
     */
    protected function getCustomCasts(): array
    {
        return [];
    }

    public function publishedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function unpublishedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function deletedBy(): MorphTo
    {
        return $this->morphTo();
    }

    public function restoredBy(): MorphTo
    {
        return $this->morphTo();
    }

    public static function getResourceName(): string
    {
        $className = class_basename(static::class);

        return strtolower($className);
    }
}
