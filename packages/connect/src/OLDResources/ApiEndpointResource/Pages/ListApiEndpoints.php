<?php

declare(strict_types=1);

namespace Moox\Connect\Resources\ApiEndpointResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Moox\Connect\Resources\ApiEndpointResource;

final class ListApiEndpoints extends ListRecords
{
    protected static string $resource = ApiEndpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
