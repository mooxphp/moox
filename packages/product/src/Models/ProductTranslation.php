<?php

declare(strict_types=1);

namespace Moox\Product\Models;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

class ProductTranslation extends BaseDraftTranslationModel
{
    /**
     * {@inheritdoc}
     */
    protected function getCustomFillable(): array
    {
        return [
            'product_id',
            'name',
            'slug',
            'short_description',
            'description',
            'meta_title',
            'meta_description',
        ];
    }
}
