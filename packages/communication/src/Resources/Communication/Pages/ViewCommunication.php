<?php

declare(strict_types=1);

namespace Moox\Communication\Resources\Communication\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Communication\Resources\CommunicationResource;

class ViewCommunication extends ViewRecord
{
    protected static string $resource = CommunicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
