<?php

namespace Moox\Press\Resources\WpTermMetaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpTermMetaResource;

class ListWpTermMetas extends ListRecords
{
    protected static string $resource = WpTermMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
