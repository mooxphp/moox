<?php

declare(strict_types=1);

namespace Moox\Address\Resources\AddressUsage\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Address\Resources\AddressUsageResource;

class EditAddressUsage extends EditRecord
{
    protected static string $resource = AddressUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
