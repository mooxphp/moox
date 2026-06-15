<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FieldOption extends Model
{
    protected $table = 'builder_field_options';

    protected $fillable = [
        'ulid',
        'field_id',
        'label',
        'value',
        'sort',
    ];

    protected $casts = [
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (FieldOption $option): void {
            if (blank($option->ulid)) {
                $option->ulid = (string) Str::ulid();
            }
        });
    }

    /**
     * @return BelongsTo<Field, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }
}
