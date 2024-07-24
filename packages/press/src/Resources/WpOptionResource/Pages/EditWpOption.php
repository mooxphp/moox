<?php

namespace Moox\Press\Resources\WpOptionResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Resources\WpOptionResource;

class EditWpOption extends EditRecord
{
    protected static string $resource = WpOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
