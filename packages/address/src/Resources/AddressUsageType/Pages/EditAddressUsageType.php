<?php

declare(strict_types=1);

namespace Moox\Address\Resources\AddressUsageType\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Address\Resources\AddressUsageTypeResource;

class EditAddressUsageType extends EditRecord
{
    protected static string $resource = AddressUsageTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
