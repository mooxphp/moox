<?php

namespace Moox\Category\Models;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

class CategoryTranslation extends BaseDraftTranslationModel
{
    /**
     * Get custom fillable for Draft translations
     */
    protected function getCustomFillable(): array
    {
        return [
            'category_id',
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
