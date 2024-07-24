<?php

namespace Moox\Press\Resources\WpOptionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpOptionResource;

class ListWpOptions extends ListRecords
{
    protected static string $resource = WpOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
