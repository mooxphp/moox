<?php

namespace Moox\Tag\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;
use Moox\User\Models\User;

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
