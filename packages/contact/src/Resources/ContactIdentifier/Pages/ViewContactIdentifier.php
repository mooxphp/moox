<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactIdentifier\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Contact\Resources\ContactIdentifierResource;

class ViewContactIdentifier extends ViewRecord
{
    protected static string $resource = ContactIdentifierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
