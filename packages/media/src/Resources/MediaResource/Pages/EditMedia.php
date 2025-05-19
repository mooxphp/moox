<?php

namespace Moox\Media\Resources\MediaCollectionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Media\Resources\MediaCollectionResource;
use Moox\Media\Models\Media;

class EditMediaCollection extends EditRecord
{
    protected static string $resource = MediaCollectionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $oldName = $this->record->name;

        Media::where('collection_name', $oldName)
            ->update(['collection_name' => $data['name']]);

        return $data;
    }
}
