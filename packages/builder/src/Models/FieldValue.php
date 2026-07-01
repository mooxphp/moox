<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Builder\Support\BuilderLocaleResolver;

class FieldValue extends Model
{
    protected $table = 'builder_field_values';

    protected $fillable = [
        'entity',
        'record_id',
        'field_name',
        'locale',
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

    protected static function booted(): void
    {
        static::creating(function (FieldValue $value): void {
            if (blank($value->locale)) {
                $value->locale = app(BuilderLocaleResolver::class)->defaultLocale();
            }
        });
    }

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

    /**
     * @param  Builder<FieldValue>  $query
     * @return Builder<FieldValue>
     */
    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }
}
