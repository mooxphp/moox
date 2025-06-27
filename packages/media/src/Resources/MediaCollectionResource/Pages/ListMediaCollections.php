<?php

namespace Moox\Media\Resources\MediaCollectionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Media\Resources\MediaCollectionResource;

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
