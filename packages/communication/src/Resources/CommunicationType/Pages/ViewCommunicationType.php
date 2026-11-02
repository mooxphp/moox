<?php

declare(strict_types=1);

namespace Moox\Communication\Resources\CommunicationType\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Communication\Resources\CommunicationTypeResource;

class ViewCommunicationType extends ViewRecord
{
    protected static string $resource = CommunicationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
