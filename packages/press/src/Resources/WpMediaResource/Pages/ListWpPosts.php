<?php

namespace Moox\Press\Resources\WpMediaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpMediaResource;

class ListWpPosts extends ListRecords
{
    protected static string $resource = WpMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
