<?php

namespace Moox\Category\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;
use Moox\User\Models\User;

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
