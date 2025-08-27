<?php

namespace Moox\Tag\Models;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

class TagTranslation extends BaseDraftTranslationModel
{
    /**
     * Get custom fillable for Draft translations
     */
    protected function getCustomFillable(): array
    {
        return [
            'tag_id',
            'title',
            'slug',
            'permalink',
            'description',
            'content',
            'author_id',
            'author_type',
        ];
    }
}
