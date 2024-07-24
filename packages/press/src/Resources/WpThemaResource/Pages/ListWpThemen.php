<?php

namespace Moox\Press\Resources\WpThemaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpThemaResource;

class ListWpThemen extends ListRecords
{
    protected static string $resource = WpThemaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
