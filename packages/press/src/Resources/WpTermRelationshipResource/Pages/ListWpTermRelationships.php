<?php

namespace Moox\Press\Resources\WpTermRelationshipResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpTermRelationshipResource;

class ListWpTermRelationships extends ListRecords
{
    protected static string $resource = WpTermRelationshipResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
