<?php

namespace Moox\MooxPressWiki\Resources\MooxPressThemaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpThemaResource;

class ViewWpThema extends ViewRecord
{
    protected static string $resource = WpThemaResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
