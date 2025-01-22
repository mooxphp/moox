<?php

declare(strict_types=1);

namespace Moox\Category\Models;

use Override;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Kalnoy\Nestedset\NodeTrait;
use Moox\Category\Database\Factories\CategoryFactory;

class Category extends Model
{
    use HasFactory;
    use NodeTrait;
    use SoftDeletes;
    protected $table = 'categories';

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

    protected static function newFactory()
    {
        return CategoryFactory::new();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categorizables(string $type): MorphToMany
    {
        return $this->morphedByMany($type, 'categorizable');
    }

    public function detachAllCategorizables(): void
    {
        DB::table('categorizables')->where('category_id', $this->id)->delete();
    }

    #[Override]
    protected static function booted(): void
    {
        static::deleting(function (Category $category): void {
            $category->detachAllCategorizables();
        });
    }
}
