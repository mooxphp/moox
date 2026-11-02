<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactIdentifierType\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Contact\Resources\ContactIdentifierTypeResource;

class EditContactIdentifierType extends EditRecord
{
    protected static string $resource = ContactIdentifierTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
