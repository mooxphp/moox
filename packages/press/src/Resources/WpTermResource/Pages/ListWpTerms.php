<?php

namespace Moox\Press\Resources\WpTermResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpTermResource;

class ListWpTerms extends ListRecords
{
    protected static string $resource = WpTermResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
