<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactRelationship\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Contact\Resources\ContactRelationshipResource;

class EditContactRelationship extends EditRecord
{
    protected static string $resource = ContactRelationshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
