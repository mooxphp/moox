<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FieldValue extends Model
{
    protected $table = 'builder_field_values';

    protected $fillable = [
        'entity',
        'record_id',
        'field_name',
        'value_string',
        'value_text',
        'value_decimal',
        'value_date',
        'value_datetime',
        'value_boolean',
        'value_json',
    ];

    protected $casts = [
        'record_id' => 'integer',
        'value_decimal' => 'decimal:6',
        'value_date' => 'date',
        'value_datetime' => 'datetime',
        'value_boolean' => 'boolean',
        'value_json' => 'array',
    ];

    /**
     * @param  Builder<FieldValue>  $query
     * @return Builder<FieldValue>
     */
    public function scopeForRecord(Builder $query, string $entity, int|string $recordId): Builder
    {
        return $query
            ->where('entity', $entity)
            ->where('record_id', $recordId);
    }
}
