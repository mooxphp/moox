<?php

namespace Moox\Press\Resources\WpTagResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Resources\WpTagResource;

class EditWpTag extends EditRecord
{
    protected static string $resource = WpTagResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
