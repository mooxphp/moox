<?php

namespace Moox\Press\Resources\WpTermResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Resources\WpTermResource;

class EditWpTerm extends EditRecord
{
    protected static string $resource = WpTermResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
