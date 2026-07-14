<?php

declare(strict_types=1);

namespace Moox\Page\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property bool $is_startpage
 * @property array $image
 * @property string $layout
 * @property string[] $translatedAttributes
 * @property string $uuid
 * @property string $ulid
 * @property-read string $title
 * @property-read string $slug
 * @property-read string $description
 * @property-read string $content
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
     * @return list<string>
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
        'layout',
        'uuid',
        'ulid',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_startpage' => 'boolean',
        'image' => 'json',
        'uuid' => 'string',
        'ulid' => 'string',
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
                        'is_startpage' => __('page::page.homepage_required'),
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

    public function mediaThroughUsables(): BelongsToMany
    {
        return $this->belongsToMany(
            Media::class,
            'media_usables',
            'media_usable_id',
            'media_id'
        )->where('media_usables.media_usable_type', '=', static::class);
    }

    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
    }
}
