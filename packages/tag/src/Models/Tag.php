<?php

declare(strict_types=1);

namespace Moox\Tag\Models;

use Override;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Moox\Tag\Database\Factories\TagFactory;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;




class Tag extends Model implements TranslatableContract, HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes, Translatable;

    protected $table = 'tags';

    public $translatedAttributes = ['title', 'slug', 'content'];

    protected $fillable = [
        'color',
        'weight',
        'count',
        'featured_image_url',
    ];

    protected $casts = [
        'weight' => 'integer',
        'count' => 'integer',
    ];

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }

    public function getStatusAttribute(): string
    {
        return $this->trashed() ? 'deleted' : 'active';
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
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();
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
