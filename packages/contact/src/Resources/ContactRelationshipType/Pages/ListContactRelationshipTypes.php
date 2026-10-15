<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactRelationshipType\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Contact\Resources\ContactRelationshipTypeResource;

class ListContactRelationshipTypes extends ListRecords
{
    protected static string $resource = ContactRelationshipTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
