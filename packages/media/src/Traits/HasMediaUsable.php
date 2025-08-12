<?php

namespace Moox\Media\Traits;

use Moox\Media\Models\MediaUsable;

trait HasMediaUsable
{
    protected static function bootHasMediaUsable()
    {
        static::deleted(function ($model) {
            MediaUsable::where('media_usable_id', $model->id)
                ->where('media_usable_type', get_class($model))
                ->delete();
        });
    }
}
