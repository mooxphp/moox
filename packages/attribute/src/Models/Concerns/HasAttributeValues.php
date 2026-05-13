<?php

declare(strict_types=1);

namespace Moox\Attribute\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Moox\Attribute\Models\AttributableAttributeValue;
use Moox\Attribute\Models\AttributeValues;

trait HasAttributeValues
{
    /**
     * Attribute value rows attached to this model (polymorphic pivot).
     *
     * @return MorphToMany<AttributeValues, $this>
     */
    public function attributeValues(): MorphToMany
    {
        return $this->morphToMany(
            related: AttributeValues::class,
            name: 'attributable',
            table: 'attributable_attribute_value',
            foreignPivotKey: 'attributable_id',
            relatedPivotKey: 'attribute_value_id',
        )->withTimestamps()
            ->using(AttributableAttributeValue::class)
            ->orderByPivot('sort_order')
            ->orderByPivot('id');
    }
}
