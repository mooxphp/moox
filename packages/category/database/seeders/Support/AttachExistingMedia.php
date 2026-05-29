<?php

declare(strict_types=1);

namespace Moox\Category\Database\Seeders\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Moox\Media\Models\Media;

final class AttachExistingMedia
{
    public static function attach(Model $model, Media $media, string $field, string $locale): void
    {
        if (! $model->exists || $model->getKey() === null) {
            return;
        }

        DB::table('media_usables')->insertOrIgnore([
            'media_id' => $media->getKey(),
            'media_usable_id' => $model->getKey(),
            'media_usable_type' => $model::class,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! is_array($model->{$field} ?? null)) {
            $model->forceFill([
                $field => [
                    'media_id' => $media->getKey(),
                    'locale' => $locale,
                ],
            ]);
            $model->saveQuietly();
        }
    }
}
