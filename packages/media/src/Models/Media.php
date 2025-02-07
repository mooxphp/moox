<?php

namespace Moox\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    public function originalModel(): MorphTo
    {
        return $this->morphTo('original_model');
    }

    public function usedInModels(): MorphToMany
    {
        return $this->morphedByMany(Model::class, 'media_usable', 'media_usables');
    }
}
