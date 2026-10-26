<?php

declare(strict_types=1);

namespace Moox\Communication\Resources\Communication\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Communication\Resources\CommunicationResource;

class ListCommunications extends ListRecords
{
    protected static string $resource = CommunicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
