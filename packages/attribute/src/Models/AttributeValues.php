<?php

namespace Moox\Attribute\Models;

use Illuminate\Database\Eloquent\Model;

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
}
