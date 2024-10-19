<?php

declare(strict_types=1);

namespace Moox\Tag\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Tag\Database\Factories\TagFactory;

class Tag extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tags';

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
        'is_active' => 'boolean',
        'weight' => 'integer',
        'count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function ($tag) {
            $tag->items()->detach();
        });
    }

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }

    public function getStatusAttribute(): string
    {
        if ($this->trashed()) {
            return 'deleted';
        }

        return 'published';
    }
}
