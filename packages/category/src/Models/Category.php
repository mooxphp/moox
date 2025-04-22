<?php

declare(strict_types=1);

namespace Moox\Category\Models;

use Override;
use Kalnoy\Nestedset\NodeTrait;
use Illuminate\Support\Facades\DB;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Category\Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;

class Category extends BaseDraftModel
{
    use HasFactory;
    use NodeTrait;
    use SoftDeletes;


    public $translatedAttributes = [
        'title',
        'status',
        'slug',
        'content'
    ];

    protected $fillable = [
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
