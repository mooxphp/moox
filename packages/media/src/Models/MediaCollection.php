<?php

namespace Moox\Media\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Moox\Localization\Models\Localization;

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

    protected static function booted()
    {
        parent::booted();

        static::deleting(function ($mediaCollection) {
            if ($mediaCollection->media()->where('write_protected', true)->exists()) {
                return false;
            }
            if ($mediaCollection->media()->exists()) {
                $uncategorized = static::whereTranslation('name', __('media::fields.uncategorized'))->first();
                if (! $uncategorized) {
                    $uncategorized = static::create([
                        'name' => __('media::fields.uncategorized'),
                        'description' => __('media::fields.uncategorized_description'),
                    ]);
                }
                $mediaCollection->media()->update([
                    'media_collection_id' => $uncategorized->getKey(),
                    'collection_name' => $uncategorized->getAttribute('name'),
                ]);
            }
        });
    }

    public static function ensureUncategorizedExists()
    {
        if (static::query()->count() > 0) {
            return;
        }

        $defaultLocale = null;
        if (class_exists(Localization::class)) {
            $localization = Localization::query()
                ->where('is_default', true)
                ->where('is_active_admin', true)
                ->with('language')
                ->first();

            if ($localization) {
                $defaultLocale = $localization->getAttribute('locale_variant') ?: $localization->language->alpha2;
            }
        }

        $locale = $defaultLocale ?: config('app.locale');

        $collection = new self;
        $translation = $collection->translateOrNew($locale);

        $previousLocale = app()->getLocale();
        app()->setLocale($locale);

        $translation->setAttribute('name', __('media::fields.uncategorized'));
        $translation->setAttribute('description', __('media::fields.uncategorized_description'));

        if ($translation->getAttribute('name') === 'media::fields.uncategorized') {
            app()->setLocale('en');
            $translation->setAttribute('name', __('media::fields.uncategorized'));
            $translation->setAttribute('description', __('media::fields.uncategorized_description'));
        }

        app()->setLocale($previousLocale);

        $collection->save();
    }
}
