<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactIdentifierType\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Contact\Resources\ContactIdentifierTypeResource;

class ListContactIdentifierTypes extends ListRecords
{
    protected static string $resource = ContactIdentifierTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
