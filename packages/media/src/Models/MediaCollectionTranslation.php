<?php

namespace Moox\Media\Models;

use Illuminate\Database\Eloquent\Model;

class MediaCollectionTranslation extends Model
{
    protected $fillable = ['name', 'description'];

    protected static function booted()
    {
        static::updated(function ($translation) {
            $collection = MediaCollection::query()->find($translation->media_collection_id);
            if ($collection) {
                $collection->media()->update([
                    'collection_name' => $collection->name,
                ]);
            }
        });
    }
}
