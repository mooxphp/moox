<?php

namespace Moox\Media\Traits;

use Illuminate\Database\Eloquent\Model;
use Moox\Media\Models\MediaUsable;

trait HasMediaUsable
{
    protected static function bootHasMediaUsable(): void
    {
        static::deleting(function (Model $model): void {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                MediaUsable::query()
                    ->where('media_usable_id', $model->getKey())
                    ->where('media_usable_type', get_class($model))
                    ->delete();

                return;
            }

            if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($model))) {
                MediaUsable::query()
                    ->where('media_usable_id', $model->getKey())
                    ->where('media_usable_type', get_class($model))
                    ->delete();
            }
        });
    }
}
