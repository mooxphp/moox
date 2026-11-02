<?php

declare(strict_types=1);

namespace Moox\Salutation\Resources\Salutation\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Salutation\Resources\SalutationResource;

class ViewSalutation extends ViewRecord
{
    protected static string $resource = SalutationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
