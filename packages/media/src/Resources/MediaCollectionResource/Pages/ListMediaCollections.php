<?php

namespace Moox\Media\Resources\MediaCollectionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Media\Resources\MediaCollectionResource;
use Filament\Actions\CreateAction;

class ListMediaCollections extends ListRecords
{
    protected static string $resource = MediaCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
