<?php

declare(strict_types=1);

namespace Moox\Address\Resources\Address\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Address\Resources\AddressResource;

class ViewAddress extends ViewRecord
{
    protected static string $resource = AddressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
