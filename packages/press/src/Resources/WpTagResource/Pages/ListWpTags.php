<?php

namespace Moox\Press\Resources\WpTagResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpTagResource;

class ListWpTags extends ListRecords
{
    protected static string $resource = WpTagResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
