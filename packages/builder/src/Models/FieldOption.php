<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Moox\Builder\Models\Concerns\HasBuilderTranslatableAttributes;

class FieldOption extends Model implements TranslatableContract
{
    use HasBuilderTranslatableAttributes;

    protected $table = 'builder_field_options';

    /** @var list<string> */
    public array $translatedAttributes = ['label'];

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
