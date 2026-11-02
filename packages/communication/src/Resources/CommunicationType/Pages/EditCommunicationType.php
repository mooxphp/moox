<?php

declare(strict_types=1);

namespace Moox\Communication\Resources\CommunicationType\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Communication\Resources\CommunicationTypeResource;

class EditCommunicationType extends EditRecord
{
    protected static string $resource = CommunicationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
