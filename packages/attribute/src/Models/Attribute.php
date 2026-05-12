<?php

namespace Moox\Attribute\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Moox\Draft\Database\Factories\AttributeFactory;
use Moox\Media\Traits\HasMediaUsable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 
 * @property string $type
 * @property string[] $translatedAttributes
 * @property string $uuid
 * @property string $ulid
 * @property-read int $author_id
 * @property-read string $author_type
 * @property-read User|null $author

 * @property-read Collection<int, Media> $media
 */
class Attribute extends BaseDraftModel implements HasMedia
{
    use HasFactory, HasMediaUsable, HasModelTaxonomy, InteractsWithMedia;

    protected function getCustomTranslatedAttributes(): array
    {
        return [
            'name',
        ];
    }

    protected $fillable = [
        'type',
        'value',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'image' => 'json',
        'due_at' => 'datetime',
        'uuid' => 'string',
        'ulid' => 'string',
        'custom_properties' => 'json',
    ];

    public static function getResourceName(): string
    {
        return 'attribute';
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
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

    protected static function newFactory()
    {
        return AttributeFactory::new();
    }
}
