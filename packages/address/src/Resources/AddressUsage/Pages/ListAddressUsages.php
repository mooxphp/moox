<?php

declare(strict_types=1);

namespace Moox\Address\Resources\AddressUsage\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Address\Resources\AddressUsageResource;

class ListAddressUsages extends ListRecords
{
    protected static string $resource = AddressUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
