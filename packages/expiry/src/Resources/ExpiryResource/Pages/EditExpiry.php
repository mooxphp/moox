<?php

namespace Moox\Expiry\Resources\ExpiryResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Expiry\Resources\ExpiryResource;

class EditExpiry extends EditRecord
{
    protected static string $resource = ExpiryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
