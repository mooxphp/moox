<?php

namespace Moox\Press\Resources\WpTermMetaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Resources\WpTermMetaResource;

class EditWpTermMeta extends EditRecord
{
    protected static string $resource = WpTermMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
