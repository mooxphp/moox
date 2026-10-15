<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactRelationshipType\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Contact\Resources\ContactRelationshipTypeResource;

class ViewContactRelationshipType extends ViewRecord
{
    protected static string $resource = ContactRelationshipTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
