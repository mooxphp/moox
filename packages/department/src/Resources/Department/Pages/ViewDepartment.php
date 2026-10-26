<?php

declare(strict_types=1);

namespace Moox\Department\Resources\Department\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Department\Resources\DepartmentResource;

class ViewDepartment extends ViewRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
