<?php

namespace Moox\Draft\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Moox\Media\Traits\HasMediaUsable;
use Moox\User\Models\User;
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
 * @property-read int $author_id
 * @property-read Carbon|null $to_publish_at
 * @property-read Carbon|null $published_at
 * @property-read Carbon|null $to_unpublish_at
 * @property-read Carbon|null $unpublished_at
 * @property-read int|null $published_by_id
 * @property-read int|null $unpublished_by_id
 * @property-read Carbon|null $deleted_at
 * @property-read int|null $deleted_by_id
 * @property-read Carbon|null $restored_at
 * @property-read int|null $restored_by_id
 * @property-read User|null $author
 * @property-read Model|null $publishedBy
 * @property-read Model|null $updatedBy
 * @property-read Model|null $createdBy
 * @property-read Model|null $unpublishedBy
 * @property-read Model|null $deletedBy
 * @property-read Model|null $restoredBy
 * @property-read Collection<int, Media> $media
 */
class Draft extends BaseDraftModel implements HasMedia
{
    use HasMediaUsable, HasModelTaxonomy, InteractsWithMedia;

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
        'data',
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
        'data' => 'json',
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

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
