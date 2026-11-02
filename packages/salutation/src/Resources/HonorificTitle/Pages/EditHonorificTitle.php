<?php

declare(strict_types=1);

namespace Moox\Salutation\Resources\HonorificTitle\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Salutation\Resources\HonorificTitleResource;

class EditHonorificTitle extends EditRecord
{
    protected static string $resource = HonorificTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
