<?php

declare(strict_types=1);

namespace Moox\Address\Resources\AddressUsageType\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Address\Resources\AddressUsageTypeResource;

class ListAddressUsageTypes extends ListRecords
{
    protected static string $resource = AddressUsageTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
