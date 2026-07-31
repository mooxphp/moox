<?php

namespace Moox\Media\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Moox\Media\Support\MediaLocaleResolver;

/**
 * @method static Builder whereTranslation(string $key, mixed $value, ?string $locale = null)
 * @method static static create(array $attributes = [])
 *
 * @property int|null $id
 * @property string|null $name
 */
class MediaCollection extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = ['name', 'description'];

    public $translatedAttributes = ['name', 'description'];

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'media_collection_id');
    }

    public function isUncategorized(): bool
    {
        $name = (string) ($this->name ?? '');

        foreach ($this->uncategorizedLabels() as $label) {
            if ($name === $label) {
                return true;
            }
        }

        return $this->translations
            ->contains(function ($translation): bool {
                $name = (string) ($translation->name ?? '');

                return in_array($name, $this->uncategorizedLabels(), true);
            });
    }

    protected static function booted()
    {
        parent::booted();

        static::deleting(function (MediaCollection $mediaCollection) {
            if ($mediaCollection->isUncategorized()) {
                return false;
            }

            if ($mediaCollection->media()->where('write_protected', true)->exists()) {
                return false;
            }

            if ($mediaCollection->media()->exists()) {
                $uncategorized = static::resolveUncategorized();
                $collectionName = $uncategorized->translate(app()->getLocale())?->getAttribute('name')
                    ?? $uncategorized->translations->first()?->getAttribute('name')
                    ?? __('media::fields.uncategorized');

                $mediaCollection->media()->update([
                    'media_collection_id' => $uncategorized->getKey(),
                    'collection_name' => $collectionName,
                ]);
            }
        });
    }

    public static function resolveUncategorized(): self
    {
        foreach (static::uncategorizedLabels() as $label) {
            $existing = static::whereTranslation('name', $label)->first();
            if ($existing) {
                return $existing;
            }
        }

        $locale = static::resolveAdminDefaultLocale();

        $collection = new self;
        $translation = $collection->translateOrNew($locale);

        $previousLocale = app()->getLocale();
        app()->setLocale($locale);

        try {
            $translation->setAttribute('name', __('media::fields.uncategorized'));
            $translation->setAttribute('description', __('media::fields.uncategorized_description'));

            if ($translation->getAttribute('name') === 'media::fields.uncategorized') {
                app()->setLocale('en_US');
                $translation->setAttribute('name', __('media::fields.uncategorized'));
                $translation->setAttribute('description', __('media::fields.uncategorized_description'));
            }
        } finally {
            app()->setLocale($previousLocale);
        }

        $collection->save();

        return $collection;
    }

    public static function ensureUncategorizedExists(): void
    {
        if (static::query()->count() > 0) {
            return;
        }

        static::resolveUncategorized();
    }

    /**
     * @return array<int, string>
     */
    private static function uncategorizedLabels(): array
    {
        return array_values(array_unique(array_filter([
            __('media::fields.uncategorized'),
            trans('media::fields.uncategorized', [], 'en_US'),
            trans('media::fields.uncategorized', [], 'de_DE'),
            trans('media::fields.uncategorized', [], 'en'),
            trans('media::fields.uncategorized', [], 'de'),
            'Uncategorized',
            'Unkategorisiert',
        ])));
    }

    private static function resolveAdminDefaultLocale(): string
    {
        return app(MediaLocaleResolver::class)->adminDefaultLocale();
    }
}
