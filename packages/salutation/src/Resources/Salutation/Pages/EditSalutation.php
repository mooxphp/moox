<?php

declare(strict_types=1);

namespace Moox\Salutation\Resources\Salutation\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Salutation\Resources\SalutationResource;

class EditSalutation extends EditRecord
{
    protected static string $resource = SalutationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
