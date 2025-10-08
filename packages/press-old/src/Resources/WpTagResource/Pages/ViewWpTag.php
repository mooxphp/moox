<?php

namespace Moox\Press\Resources\WpTagResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpTagResource;

class ViewWpTag extends ViewRecord
{
    protected static string $resource = WpTagResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
