<?php

namespace Moox\Press\Resources\WpCommentResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpCommentResource;

class ListWpComments extends ListRecords
{
    protected static string $resource = WpCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
