<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactRelationship\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Contact\Resources\ContactRelationshipResource;

class ListContactRelationships extends ListRecords
{
    protected static string $resource = ContactRelationshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
