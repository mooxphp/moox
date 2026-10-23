<?php

declare(strict_types=1);

namespace Moox\Communication\Resources\CommunicationType\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Communication\Resources\CommunicationTypeResource;

class ListCommunicationTypes extends ListRecords
{
    protected static string $resource = CommunicationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
