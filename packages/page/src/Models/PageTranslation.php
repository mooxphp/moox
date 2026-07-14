<?php

declare(strict_types=1);

namespace Moox\Page\Models;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;
use Moox\Core\Enums\TranslationStatus;

class PageTranslation extends BaseDraftTranslationModel
{
    protected function getCustomCasts(): array
    {
        return [
            'content' => 'array',
            'translation_status' => TranslationStatus::class,
        ];
    }

    /**
     * @return list<string>
     */
    protected function getCustomFillable(): array
    {
        return [
            'page_id',
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
