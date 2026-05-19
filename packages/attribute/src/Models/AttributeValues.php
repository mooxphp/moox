<?php

namespace Moox\Attribute\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeValues extends Model
{
    protected $table = 'attribute_values';

    protected $fillable = [
        'attribute_id',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * @return HasMany<AttributableAttributeValue, $this>
     */
    public function attributableAssignments(): HasMany
    {
        return $this->hasMany(AttributableAttributeValue::class, 'attribute_value_id');
    }
}
