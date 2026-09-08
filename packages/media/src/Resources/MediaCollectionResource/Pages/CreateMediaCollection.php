<?php

declare(strict_types=1);

namespace Moox\Media\Resources\MediaCollectionResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseCreateStaticRecord;
use Moox\Media\Models\MediaCollection;
use Moox\Media\Resources\MediaCollectionResource;

class CreateMediaCollection extends BaseCreateStaticRecord
{
    protected static string $resource = MediaCollectionResource::class;

    /**
     * Media collections store locale_variant (e.g. de_DE), not alpha2.
     * Translation model also omits `locale` from $fillable, so BaseCreateStaticRecord's
     * firstOrNew/save path cannot persist locale — use Astrotomic translateOrNew instead.
     */
    protected function handleRecordCreation(array $data): MediaCollection
    {
        if (isset($data['extend_existing_collection']) && $data['extend_existing_collection']) {
            $existingCollection = MediaCollection::query()->find($data['extend_existing_collection']);
            if ($existingCollection) {
                $translation = $existingCollection->translateOrNew($this->lang);
                $translation->setAttribute('name', $data['name']);
                $translation->setAttribute('description', $data['description'] ?? '');
                $existingCollection->save();

                $this->record = $existingCollection;

                return $existingCollection;
            }
        }

        unset($data['extend_existing_collection']);

        $collection = new MediaCollection;
        $translation = $collection->translateOrNew($this->lang);
        $translation->setAttribute('name', $data['name']);
        $translation->setAttribute('description', $data['description'] ?? '');
        $collection->save();

        return $collection;
    }
}
