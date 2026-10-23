<?php

declare(strict_types=1);

namespace Moox\Address\Resources\AddressUsage\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Address\Resources\AddressUsageResource;

class ViewAddressUsage extends ViewRecord
{
    protected static string $resource = AddressUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
