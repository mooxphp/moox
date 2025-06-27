<?php

namespace Moox\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MediaCollection extends Model
{
    protected $fillable = ['name', 'description'];

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'collection_name', 'name');
    }

    protected static function booted()
    {
        static::deleting(function ($mediaCollection) {
            if ($mediaCollection->media()->where('write_protected', true)->exists()) {
                return false;
            }
            if ($mediaCollection->media()->exists()) {
                $uncategorized = static::firstOrCreate(
                    ['name' => __('media::fields.uncategorized')],
                    ['description' => __('media::fields.uncategorized_description')]
                );

                $mediaCollection->media()->update(['collection_name' => __('media::fields.uncategorized')]);
            }
        });

        static::updated(function ($mediaCollection) {
            if ($mediaCollection->isDirty('name')) {
                Media::where('collection_name', $mediaCollection->getOriginal('name'))
                    ->update(['collection_name' => $mediaCollection->name]);
            }
        });
    }
}
