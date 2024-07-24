<?php

namespace Moox\Press\Resources\WpOptionResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpOptionResource;

class ViewWpOption extends ViewRecord
{
    protected static string $resource = WpOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
