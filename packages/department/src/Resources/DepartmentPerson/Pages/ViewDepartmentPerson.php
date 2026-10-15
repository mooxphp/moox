<?php

declare(strict_types=1);

namespace Moox\Department\Resources\DepartmentPerson\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Department\Resources\DepartmentPersonResource;

class ViewDepartmentPerson extends ViewRecord
{
    protected static string $resource = DepartmentPersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
