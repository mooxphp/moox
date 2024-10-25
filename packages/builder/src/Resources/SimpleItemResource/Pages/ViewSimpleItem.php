<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\SimpleItemResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Builder\Resources\SimpleItemResource;

class ViewSimpleItem extends ViewRecord
{
    protected static string $resource = SimpleItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
