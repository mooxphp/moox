<?php

namespace Moox\Press\Resources\WpMediaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Resources\WpMediaResource;

class EditWpPost extends EditRecord
{
    protected static string $resource = WpMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
