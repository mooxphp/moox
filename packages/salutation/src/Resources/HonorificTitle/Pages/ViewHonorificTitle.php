<?php

declare(strict_types=1);

namespace Moox\Salutation\Resources\HonorificTitle\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Salutation\Resources\HonorificTitleResource;

class ViewHonorificTitle extends ViewRecord
{
    protected static string $resource = HonorificTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
