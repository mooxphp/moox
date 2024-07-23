<?php

namespace Moox\Press\Resources\WpPostMetaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpPostMetaResource;

class ListWpPostMetas extends ListRecords
{
    protected static string $resource = WpPostMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
