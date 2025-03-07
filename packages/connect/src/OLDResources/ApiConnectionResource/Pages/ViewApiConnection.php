<?php

declare(strict_types=1);

namespace Moox\Connect\Resources\ApiConnectionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Moox\Connect\Resources\ApiConnectionResource;

final class ViewApiConnection extends ViewRecord
{
    protected static string $resource = ApiConnectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
