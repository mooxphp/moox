<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactRelationship\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Contact\Resources\ContactRelationshipResource;

class ViewContactRelationship extends ViewRecord
{
    protected static string $resource = ContactRelationshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
