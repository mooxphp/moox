<?php

namespace Moox\News\Models;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;
use Moox\User\Models\User;

class NewsTranslation extends BaseDraftTranslationModel
{
    /**
     * Get custom fillable for Draft translations
     */
    protected function getCustomFillable(): array
    {
        return [
            'news_id',
            'title',
            'slug',
            'permalink',
            'description',
            'content',
            'author_id',
            'author_type',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->author_id && ! $model->author_type) {
                $model->author_type = User::class;
            }
        });

        static::updating(function ($model) {
            if ($model->author_id && ! $model->author_type) {
                $model->author_type = User::class;
            }
        });
    }
}
