<?php

namespace Moox\Press\Resources\WpPageResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Resources\WpPageResource;

class EditWpPost extends EditRecord
{
    protected static string $resource = WpPageResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
