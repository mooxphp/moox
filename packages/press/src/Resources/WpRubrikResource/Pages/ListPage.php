<?php

namespace Moox\Press\Resources\WpRubrikResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpRubrikResource;

class ListPage extends ListRecords
{
    protected static string $resource = WpRubrikResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
