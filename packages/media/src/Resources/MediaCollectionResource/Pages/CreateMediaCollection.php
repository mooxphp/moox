<?php

namespace Moox\Media\Resources\MediaCollectionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Media\Models\MediaCollection;
use Moox\Media\Resources\MediaCollectionResource;

class CreateMediaCollection extends CreateRecord
{
    protected static string $resource = MediaCollectionResource::class;

    protected function handleRecordCreation(array $data): MediaCollection
    {
        if (isset($data['extend_existing_collection']) && $data['extend_existing_collection']) {
            $existingCollection = MediaCollection::find($data['extend_existing_collection']);
            if ($existingCollection) {
                $existingCollection->translateOrNew(app()->getLocale())->name = $data['name'];
                $existingCollection->translateOrNew(app()->getLocale())->description = $data['description'] ?? '';
                $existingCollection->save();

                $this->record = $existingCollection;

                return $existingCollection;
            }
        }

        unset($data['extend_existing_collection']);

        return MediaCollection::create($data);
    }
}
