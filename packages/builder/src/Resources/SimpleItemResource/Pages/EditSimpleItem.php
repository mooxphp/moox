<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\SimpleItemResource\Pages;

use Filament\Pages\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Builder\Resources\SimpleItemResource;

class EditSimpleItem extends EditRecord
{
    protected static string $resource = SimpleItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
