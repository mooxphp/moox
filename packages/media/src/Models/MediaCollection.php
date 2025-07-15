<?php

namespace Moox\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaCollection extends Model implements TranslatableContract
{
    use Translatable;

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
                if (!$uncategorized) {
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
}
