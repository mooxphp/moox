<?php

declare(strict_types=1);

namespace Moox\Connect\Resources\ApiEndpointResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Moox\Connect\Resources\ApiEndpointResource;

final class EditApiEndpoint extends EditRecord
{
    protected static string $resource = ApiEndpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ViewAction::make(),
        ];
    }
}
