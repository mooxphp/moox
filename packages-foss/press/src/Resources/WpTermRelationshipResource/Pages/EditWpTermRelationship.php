<?php

namespace Moox\Press\Resources\WpTermRelationshipResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Resources\WpTermRelationshipResource;

class EditWpTermRelationship extends EditRecord
{
    protected static string $resource = WpTermRelationshipResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
