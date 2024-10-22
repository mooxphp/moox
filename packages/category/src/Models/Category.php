<?php

declare(strict_types=1);

namespace Moox\Category\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;
use Moox\Category\Database\Factories\CategoryFactory;

class Category extends Model
{
    use HasFactory, NodeTrait, SoftDeletes;

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
        return $this->trashed() ? 'deleted' : 'published';
    }

    protected static function newFactory()
    {
        return CategoryFactory::new();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
