<?php

namespace Moox\Media\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator as BasePathGenerator;

class CustomPathGenerator extends BasePathGenerator
{
    public function getPath(Media $media) : string
    {
        $originalMediaId = $media->getCustomProperty('original_media_id') ?? $media->id;

        return "media/{$originalMediaId}/";
    }


    public function getPathForConversions(Media $media): string
    {
        $originalMediaId = $media->getCustomProperty('original_media_id') ?? $media->id;

        return "media/{$originalMediaId}/conversions/";
    }


    public function getPathForResponsiveImages(Media $media): string
    {
        $originalMediaId = $media->getCustomProperty('original_media_id') ?? $media->id;

        return "media/{$originalMediaId}/responsive-images/";
    }
}
