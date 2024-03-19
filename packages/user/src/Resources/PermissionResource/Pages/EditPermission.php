<?php declare(strict_types=1);

namespace Moox\User\Resources\PermissionResource\Pages;

use Moox\User\Resources\PermissionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPermission extends EditRecord
{
    public static function getResource(): string
    {
        return  PermissionResource::class;
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
