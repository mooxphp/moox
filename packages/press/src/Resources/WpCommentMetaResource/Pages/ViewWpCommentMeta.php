<?php

namespace Moox\Press\Resources\WpCommentMetaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpCommentMetaResource;

class ViewWpCommentMeta extends ViewRecord
{
    protected static string $resource = WpCommentMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
