<?php

namespace Moox\Attribute\Models;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

class AttributeTranslation extends BaseDraftTranslationModel
{
    /**
     * Get custom fillable for Draft translations
     */
    protected function getCustomFillable(): array
    {
        return [
            'attribute_id',
            'author_id',
            'author_type',
            'value',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Base draft translations override {@see Model::getCasts()}; child models must register casts here.
     */
    protected function getCustomCasts(): array
    {
        return [
            'value' => 'json',
        ];
    }
}
