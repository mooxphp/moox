<?php

declare(strict_types=1);

namespace Moox\Department\Resources\DepartmentPerson\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Department\Resources\DepartmentPersonResource;

class EditDepartmentPerson extends EditRecord
{
    protected static string $resource = DepartmentPersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
