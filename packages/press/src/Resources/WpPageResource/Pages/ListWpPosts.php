<?php

namespace Moox\Press\Resources\WpPageResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpPageResource;

class ListWpPosts extends ListRecords
{
    protected static string $resource = WpPageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
