<?php

declare(strict_types=1);

namespace Moox\User\Resources\PermissionResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\User\Resources\PermissionResource;

class EditPermission extends EditRecord
{
    public static function getResource(): string
    {
        return PermissionResource::class;
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
