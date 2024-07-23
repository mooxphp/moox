<?php

namespace Moox\Press\Resources\WpCommentMetaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpCommentMetaResource;

class ListWpCommentMetas extends ListRecords
{
    protected static string $resource = WpCommentMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
