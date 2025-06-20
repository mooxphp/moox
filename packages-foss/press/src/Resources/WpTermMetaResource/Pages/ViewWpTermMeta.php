<?php

namespace Moox\Press\Resources\WpTermMetaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpTermMetaResource;

class ViewWpTermMeta extends ViewRecord
{
    protected static string $resource = WpTermMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
