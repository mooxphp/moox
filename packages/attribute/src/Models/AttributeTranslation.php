<?php

namespace Moox\Attribute\Models;

use Moox\Core\Entities\Items\Draft\BaseDraftTranslationModel;

class AttributeTranslation extends BaseDraftTranslationModel
{
    protected $casts = [
        'value' => 'json',
    ];

    /**
     * Get custom fillable for Draft translations
     */
    protected function getCustomFillable(): array
    {
        return [
            'attribute_id',
            'author_id',
            'author_type',
        ];
    }
}
