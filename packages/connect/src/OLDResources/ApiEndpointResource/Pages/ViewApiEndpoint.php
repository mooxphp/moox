<?php

declare(strict_types=1);

namespace Moox\Connect\Resources\ApiEndpointResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Moox\Connect\Resources\ApiEndpointResource;

final class ViewApiEndpoint extends ViewRecord
{
    protected static string $resource = ApiEndpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
