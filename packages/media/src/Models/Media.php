<?php

namespace Moox\Media\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    public function originalModel(): MorphTo
    {
        return $this->morphTo('original_model');
    }

    public function mediaable()
    {
        return $this->morphTo();
    }

}
