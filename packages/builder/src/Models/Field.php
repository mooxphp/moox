<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Moox\Builder\Models\Concerns\HasBuilderTranslatableAttributes;

class Field extends Model implements TranslatableContract
{
    use HasBuilderTranslatableAttributes;

    protected $table = 'builder_fields';

    /** @var list<string> */
    public array $translatedAttributes = ['label'];

    protected $fillable = [
        'ulid',
        'field_group_id',
        'parent_field_id',
        'name',
        'label',
        'type',
        'config',
        'validation',
        'conditional_logic',
        'settings',
        'sort',
    ];

    protected $casts = [
        'config' => 'array',
        'validation' => 'array',
        'conditional_logic' => 'array',
        'settings' => 'array',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Field $field): void {
            if (blank($field->ulid)) {
                $field->ulid = (string) Str::ulid();
            }
        });

        static::deleting(function (Field $field): void {
            $field->children()->each(fn (Field $child) => $child->delete());
        });
    }

    /**
     * @return BelongsTo<FieldGroup, $this>
     */
    public function fieldGroup(): BelongsTo
    {
        return $this->belongsTo(FieldGroup::class);
    }

    /**
     * @return BelongsTo<Field, $this>
     */
    public function parentField(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_field_id');
    }

    /**
     * @return HasMany<Field, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_field_id')->orderBy('sort');
    }

    /**
     * @return HasMany<FieldOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(FieldOption::class)->orderBy('sort');
    }
}
