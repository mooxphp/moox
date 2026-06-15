<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FieldGroup extends Model
{
    protected $table = 'builder_field_groups';

    protected $attributes = [
        'placement' => 'default',
        'sort' => 0,
        'active' => true,
    ];

    protected $fillable = [
        'ulid',
        'name',
        'slug',
        'location_rules',
        'placement',
        'settings',
        'sort',
        'active',
    ];

    protected $casts = [
        'location_rules' => 'array',
        'settings' => 'array',
        'sort' => 'integer',
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (FieldGroup $group): void {
            if (blank($group->ulid)) {
                $group->ulid = (string) Str::ulid();
            }
        });
    }

    /**
     * @param  Builder<FieldGroup>  $query
     * @return Builder<FieldGroup>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * @return HasMany<Field, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(Field::class)->orderBy('sort');
    }
}
