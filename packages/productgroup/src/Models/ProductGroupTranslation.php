<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Models;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

class ProductGroupTranslation extends BaseDraftTranslationModel
{
    /**
     * {@inheritdoc}
     */
    protected function getCustomFillable(): array
    {
        return [
            'product_group_id',
            'name',
            'slug',
            'short_description',
            'description',
            'meta_title',
            'meta_description',
        ];
    }
}
