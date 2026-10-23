<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactIdentifier\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Contact\Resources\ContactIdentifierResource;

class EditContactIdentifier extends EditRecord
{
    protected static string $resource = ContactIdentifierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
