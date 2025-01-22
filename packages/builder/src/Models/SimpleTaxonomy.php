<?php

declare(strict_types=1);

namespace Moox\Builder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Override;

class SimpleTaxonomy extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'simple_taxonomies';

    protected function getResourceName(): string
    {
        return 'simple_taxonomy';
    }

    protected $fillable = [
        'title',
        'slug',
        'content',
        'color',
        'weight',
        'count',
        'featured_image_url',
    ];

    protected $casts = [
        'weight' => 'integer',
        'count' => 'integer',
    ];

    public function getStatusAttribute(): string
    {
        return $this->trashed() ? 'deleted' : 'active';
    }

    public function simpletaxonomyables(string $type): MorphToMany
    {
        return $this->morphedByMany($type, 'simpletaxonomyable');
    }

    public function detachAllSimpletaxonomyables(): void
    {
        DB::table('simpletaxonomyables')->where('simple_taxonomy_id', $this->id)->delete();
    }

    #[Override]
    protected static function booted(): void
    {
        static::deleting(function (SimpleTaxonomy $simpleTaxonomy): void {
            $simpleTaxonomy->detachAllSimpletaxonomyables();
        });
    }
}
