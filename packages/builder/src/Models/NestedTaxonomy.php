<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Override;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Kalnoy\Nestedset\NodeTrait;

class NestedTaxonomy extends Model
{
    use HasFactory;
    use NodeTrait;
    use SoftDeletes;
    protected $table = 'nested_taxonomies';

    protected function getResourceName(): string
    {
        return 'nested_taxonomy';
    }

    protected $fillable = [
        'title',
        'slug',
        'content',
        'color',
        'weight',
        'count',
        'featured_image_url',
        'parent_id',
    ];

    protected $casts = [
        'weight' => 'integer',
        'count' => 'integer',
    ];

    public function getStatusAttribute(): string
    {
        return $this->trashed() ? 'deleted' : 'active';
    }

    public function parent(): ?BelongsTo
    {
        return $this->belongsTo(NestedTaxonomy::class, 'parent_id');
    }

    public function nestedtaxonomyables(string $type): MorphToMany
    {
        return $this->morphedByMany($type, 'nestedtaxonomyable');
    }

    public function detachAllNestedtaxonomyables(): void
    {
        DB::table('nestedtaxonomyables')->where('nested_taxonomy_id', $this->id)->delete();
    }

    #[Override]
    protected static function booted(): void
    {
        static::deleting(function (NestedTaxonomy $nestedTaxonomy): void {
            $nestedTaxonomy->detachAllNestedtaxonomyables();
        });
    }
}
