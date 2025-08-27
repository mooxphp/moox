<?php

namespace Moox\Draft\Models;

use Moox\User\Models\User;
use Moox\Draft\Enums\TranslationStatus;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

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
