<?php

declare(strict_types=1);

namespace Moox\Person\Resources\Person\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Person\Resources\PersonResource;

class ListPersons extends ListRecords
{
    protected static string $resource = PersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
