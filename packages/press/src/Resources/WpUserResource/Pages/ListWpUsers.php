<?php

namespace Moox\Press\Resources\WpUserResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpUserResource;

class ListWpUsers extends ListRecords
{
    protected static string $resource = WpUserResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
