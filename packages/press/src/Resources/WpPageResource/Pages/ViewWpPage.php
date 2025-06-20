<?php

namespace Moox\Press\Resources\WpPageResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpPageResource;

class ViewWpPage extends ViewRecord
{
    protected static string $resource = WpPageResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
