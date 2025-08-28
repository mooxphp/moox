<?php

namespace Moox\Media\Traits;

use Moox\Media\Models\MediaUsable;

trait HasMediaUsable
{
    protected static function bootHasMediaUsable()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                MediaUsable::where('media_usable_id', $model->id)
                    ->where('media_usable_type', get_class($model))
                    ->delete();

                return;
            }

            if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($model))) {
                MediaUsable::where('media_usable_id', $model->id)
                    ->where('media_usable_type', get_class($model))
                    ->delete();
            }
        });
    }
}
