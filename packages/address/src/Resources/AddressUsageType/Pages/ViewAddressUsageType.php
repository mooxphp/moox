<?php

declare(strict_types=1);

namespace Moox\Address\Resources\AddressUsageType\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Address\Resources\AddressUsageTypeResource;

class ViewAddressUsageType extends ViewRecord
{
    protected static string $resource = AddressUsageTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
