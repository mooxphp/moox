<?php

namespace Moox\Press\Resources\WpCommentMetaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Resources\WpCommentMetaResource;

class EditWpCommentMeta extends EditRecord
{
    protected static string $resource = WpCommentMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
