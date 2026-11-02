<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactIdentifierType\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Contact\Resources\ContactIdentifierTypeResource;

class ViewContactIdentifierType extends ViewRecord
{
    protected static string $resource = ContactIdentifierTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
