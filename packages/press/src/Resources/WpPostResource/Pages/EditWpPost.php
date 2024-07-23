<?php

namespace Moox\Press\Resources\WpPostResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Resources\WpPostResource;

class EditWpPost extends EditRecord
{
    protected static string $resource = WpPostResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
