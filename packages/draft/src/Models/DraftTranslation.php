<?php

namespace Moox\Draft\Models;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;
use Moox\Draft\Enums\TranslationStatus;

class DraftTranslation extends BaseDraftTranslationModel
{
    protected $casts = [
        'translation_status' => TranslationStatus::class,
    ];

    /**
     * Get custom fillable for Draft translations
     */
    protected function getCustomFillable(): array
    {
        return [
            'draft_id',
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
