<?php

namespace Moox\Page\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Moox\Media\Traits\HasMediaUsable;
use Moox\Page\Database\Factories\PageFactory;
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
 * @property-read int|null $author_id
 * @property-read string|null $author_type

 * @property-read Collection<int, Media> $media
 */
class Page extends BaseDraftModel implements HasMedia
{
    use HasFactory, HasMediaUsable, HasModelTaxonomy, InteractsWithMedia;

    /**
     * @return array<string, string>
     */
    public static function layoutOptions(): array
    {
        $layouts = config('page.layouts', []);

        return collect($layouts)
            ->mapWithKeys(function (array|string $layout, string $key): array {
                if (is_array($layout)) {
                    return [$key => (string) ($layout['label'] ?? $key)];
                }

                return [$key => $layout];
            })
            ->all();
    }

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
        'is_startpage',
        'image',
        'type',
        'layout',
        'due_at',
        'status',
        'uuid',
        'ulid',
        'custom_properties',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_startpage' => 'boolean',
        'image' => 'json',
        'due_at' => 'datetime',
        'uuid' => 'string',
        'ulid' => 'string',
        'custom_properties' => 'json',
    ];

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopeHomepage(Builder $query): Builder
    {
        return $query->where('is_startpage', true);
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page): void {
            if ($page->isDirty('is_startpage') && ! $page->is_startpage && $page->getOriginal('is_startpage')) {
                $hasReplacementHomepage = static::query()
                    ->homepage()
                    ->when($page->exists, fn (Builder $query) => $query->whereKeyNot($page->getKey()))
                    ->exists();

                if (! $hasReplacementHomepage) {
                    throw ValidationException::withMessages([
                        'is_startpage' => 'Es muss immer eine Startseite geben.',
                    ]);
                }
            }

            if ($page->is_startpage) {
                static::query()
                    ->homepage()
                    ->when($page->exists, fn (Builder $query) => $query->whereKeyNot($page->getKey()))
                    ->update(['is_startpage' => false]);
            }
        });
    }

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

    protected static function newFactory()
    {
        return PageFactory::new();
    }
}
