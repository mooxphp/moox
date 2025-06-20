<?php

namespace Moox\Press\Resources\WpCommentResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Resources\WpCommentResource;

class EditWpComment extends EditRecord
{
    protected static string $resource = WpCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
