<?php

declare(strict_types=1);

namespace Moox\Attribute\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Pivot between any Eloquent model and an {@see AttributeValues} row.
 *
 * @property-read \Illuminate\Database\Eloquent\Model $attributable
 * @property-read AttributeValues $attributeValue
 * @property int $sort_order
 */
class AttributableAttributeValue extends MorphPivot
{
    protected $table = 'attributable_attribute_value';

    public $incrementing = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'attribute_value_id',
        'attributable_type',
        'attributable_id',
        'sort_order',
    ];

    public function attributable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'attributable_type', 'attributable_id');
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValues::class, 'attribute_value_id');
    }
}
