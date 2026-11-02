<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactRelationshipType\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Contact\Resources\ContactRelationshipTypeResource;

class EditContactRelationshipType extends EditRecord
{
    protected static string $resource = ContactRelationshipTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
