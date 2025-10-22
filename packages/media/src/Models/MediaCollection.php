<?php

namespace Moox\Media\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Moox\Localization\Models\Localization;

class MediaCollection extends Model implements TranslatableContract
{
    use Translatable;

    public $translatedAttributes = ['name', 'description'];

    protected $fillable = ['name', 'description'];

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
                    'media_collection_id' => $uncategorized->id,
                    'collection_name' => $uncategorized->name,
                ]);
            }
        });
    }

    public static function ensureUncategorizedExists()
    {
        if (static::count() > 0) {
            return;
        }

        $defaultLocale = null;
        if (class_exists(Localization::class)) {
            $localization = Localization::where('is_default', true)
                ->where('is_active_admin', true)
                ->with('language')
                ->first();

            if ($localization && $localization->language) {
                $defaultLocale = $localization->locale_variant ?: $localization->language->alpha2;
            }
        }

        $locale = $defaultLocale ?: config('app.locale');

        $collection = new static;
        $translation = $collection->translateOrNew($locale);

        $previousLocale = app()->getLocale();
        app()->setLocale($locale);

        $translation->name = __('media::fields.uncategorized');
        $translation->description = __('media::fields.uncategorized_description');

        if ($translation->name === 'media::fields.uncategorized') {
            app()->setLocale('en');
            $translation->name = __('media::fields.uncategorized');
            $translation->description = __('media::fields.uncategorized_description');
        }

        app()->setLocale($previousLocale);

        $collection->save();
    }
}
