<?php

declare(strict_types=1);

namespace Moox\Tag\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Media\Traits\HasMediaUsable;
use Moox\Tag\Database\Factories\TagFactory;
use Override;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Tag extends BaseDraftModel implements HasMedia
{
    use HasFactory, HasMediaUsable, InteractsWithMedia;

    /**
     * Get custom translated attributes for Draft
     */
    protected function getCustomTranslatedAttributes(): array
    {
        return [
            'title',
            'slug',
            'permalink',
            'description',
            'content',
            'author_id',
            'author_type',
        ];
    }

    protected $fillable = [
        'color',
        'weight',
        'count',
        'image',
        'is_active',
        'due_at',
        'uuid',
        'ulid',
        'custom_properties',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'due_at' => 'datetime',
        'image' => 'json',
        'uuid' => 'string',
        'ulid' => 'string',
        'custom_properties' => 'json',
    ];

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }

    public function taggables(string $type): MorphToMany
    {
        return $this->morphedByMany($type, 'taggable');
    }

    public function detachAllTaggables(): void
    {
        DB::table('taggables')->where('tag_id', $this->id)->delete();
    }

    #[Override]
    protected static function booted(): void
    {
        static::deleting(function (Tag $tag): void {
            $tag->detachAllTaggables();
        });
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->nonQueued()
            ->fit(Fit::Contain, 300, 300);
    }

    public function mediaThroughUsables()
    {
        return $this->belongsToMany(
            Media::class,
            'media_usables',
            'media_usable_id',
            'media_id'
        )->where('media_usables.media_usable_type', '=', static::class);
    }
}
