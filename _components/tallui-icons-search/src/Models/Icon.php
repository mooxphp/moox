<?php

declare(strict_types=1);

namespace Usetall\TalluiIconsSearch\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

/**
 * @property array<string> $keywords
 */
final class Icon extends Model
{
    use Searchable;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'keywords' => 'array',
    ];


    public function set(): BelongsTo
    {
        return $this->belongsTo(IconSet::class, 'icon_set_id');
    }

    public static function relatedIcons(self $icon): Collection
    {
        return static::search(implode('-', $icon->keywords))
            ->get()
            ->where('id', '!=', $icon->id);
    }

    /** @return array<array> */
    public function toSearchableArray()
    {
        return $this->only('id', 'icon_set_id', 'keywords');
    }

    public function scopeWithSet(Builder $query, string $set): Builder
    {
        return $query->when(!empty($set), fn ($query) => $query->where('icon_set_id', $set));
    }

    public function getRouteKeyName(): string
    {
        return 'name';
    }
}
