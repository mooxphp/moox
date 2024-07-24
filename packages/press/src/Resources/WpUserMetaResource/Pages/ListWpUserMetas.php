<?php

namespace Moox\Press\Resources\WpUserMetaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpUserMetaResource;

class ListWpUserMetas extends ListRecords
{
    protected static string $resource = WpUserMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
