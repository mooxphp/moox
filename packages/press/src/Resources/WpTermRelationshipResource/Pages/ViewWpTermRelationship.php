<?php

namespace Moox\Press\Resources\WpTermRelationshipResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpTermRelationshipResource;

class ViewWpTermRelationship extends ViewRecord
{
    protected static string $resource = WpTermRelationshipResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
