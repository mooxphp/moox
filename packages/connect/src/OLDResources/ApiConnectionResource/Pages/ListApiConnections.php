<?php

declare(strict_types=1);

namespace Moox\Connect\Resources\ApiConnectionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Moox\Connect\Resources\ApiConnectionResource;

final class ListApiConnections extends ListRecords
{
    protected static string $resource = ApiConnectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
