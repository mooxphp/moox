<?php declare(strict_types=1);

namespace Moox\User\Resources\PermissionResource\Pages;

use Moox\User\Resources\PermissionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePermission extends CreateRecord
{
    public static function getResource(): string
    {
        return PermissionResource::class;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['guard_name'] = config('user.guard_name');

        return $data;
    }
}
