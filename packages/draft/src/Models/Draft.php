<?php

namespace Moox\Draft\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Core\Models\Concerns\HasScopedModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Moox\Draft\Database\Factories\DraftFactory;
use Moox\Media\Traits\HasMediaUsable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property bool $is_active
 * @property array $data
 * @property array $image
 * @property string $type
 * @property string[] $translatedAttributes
 * @property Carbon|null $due_at
 * @property string $uuid
 * @property string $ulid
 * @property-read string $title
 * @property-read string $slug
 * @property-read string $description
 * @property-read string $content
 * @property-read string $status
 * @property string|\Moox\Core\Support\Scopes\ScopeValue|null $scope
 * @property-read int $author_id
 * @property-read string $author_type
 * @property-read User|null $author

 * @property-read Collection<int, Media> $media
 */
class Draft extends BaseDraftModel implements HasMedia
{
    use HasFactory, HasMediaUsable, HasModelTaxonomy, HasScopedModel, InteractsWithMedia;

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
        'is_active',
        'image',
        'type',
        'color',
        'due_at',
        'status',
        'uuid',
        'ulid',
        'custom_properties',
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
        return 'draft';
    }

    public function getDefaultScope(): ?string
    {
        return \Moox\Core\Support\Scopes\ScopeValue::forKeyString(
            static::getResourceName(),
            boundary: config('draft.resources.draft.boundary'),
            source: config('draft.resources.draft.source'),
            context: config('draft.resources.draft.context'),
        );
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
        return DraftFactory::new();
    }
}
