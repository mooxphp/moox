<?php

namespace Moox\Press\Resources\WpCommentResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpCommentResource;

class ViewWpComment extends ViewRecord
{
    protected static string $resource = WpCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
