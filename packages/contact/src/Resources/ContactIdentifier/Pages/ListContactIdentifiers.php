<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactIdentifier\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Contact\Resources\ContactIdentifierResource;

class ListContactIdentifiers extends ListRecords
{
    protected static string $resource = ContactIdentifierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
