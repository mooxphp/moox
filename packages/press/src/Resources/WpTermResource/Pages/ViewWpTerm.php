<?php

namespace Moox\Press\Resources\WpTermResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpTermResource;

class ViewWpTerm extends ViewRecord
{
    protected static string $resource = WpTermResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
