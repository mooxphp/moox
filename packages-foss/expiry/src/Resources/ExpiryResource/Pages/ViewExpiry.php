<?php

namespace Moox\Expiry\Resources\ExpiryResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Expiry\Resources\ExpiryResource;

class ViewExpiry extends ViewRecord
{
    protected static string $resource = ExpiryResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
