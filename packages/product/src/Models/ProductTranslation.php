<?php

declare(strict_types=1);

namespace Moox\Product\Models;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;
use Moox\User\Models\User;

class ProductTranslation extends BaseDraftTranslationModel
{
    /**
     * {@inheritdoc}
     */
    protected function getCustomFillable(): array
    {
        return [
            'product_id',
            'title',
            'slug',
            'permalink',
            'subtitle',
            'excerpt',
            'description',
            'content',
            'meta_title',
            'meta_description',
            'author_id',
            'author_type',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ProductTranslation $model): void {
            if ($model->author_id && ! $model->author_type) {
                $model->author_type = User::class;
            }
        });

        static::updating(function (ProductTranslation $model): void {
            if ($model->author_id && ! $model->author_type) {
                $model->author_type = User::class;
            }
        });
    }
}
