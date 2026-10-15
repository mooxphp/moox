<?php

declare(strict_types=1);

namespace Moox\Department\Resources\DepartmentPerson\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Department\Resources\DepartmentPersonResource;

class ListDepartmentPersons extends ListRecords
{
    protected static string $resource = DepartmentPersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
