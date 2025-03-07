<?php

declare(strict_types=1);

namespace Moox\Connect\Resources\ApiConnectionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Moox\Connect\Resources\ApiConnectionResource;

final class EditApiConnection extends EditRecord
{
    protected static string $resource = ApiConnectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ViewAction::make(),
        ];
    }
}
