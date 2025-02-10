<?php

namespace Moox\Media\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use Illuminate\Support\Facades\Storage;

class Media extends BaseMedia
{
    public function originalModel(): MorphTo
    {
        return $this->morphTo('original_model');
    }

    public function getUrl(string $conversionName = ''): string
    {
        return Storage::disk($this->disk ?? 'public')->url("media/{$this->id}/{$this->file_name}");
    }

}
