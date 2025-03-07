<?php

namespace Moox\Media\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'file_name',
        'disk',
        'mime_type',
        'size',
        'custom_properties',
        'responsive_images',
        'model_id',
        'model_type',
        'collection_name',
        'title',
        'alt',
        'description',
        'internal_note',
        'original_model_id',
        'original_model_type',
    ];

    public function registerMediaConversions(?BaseMedia $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
