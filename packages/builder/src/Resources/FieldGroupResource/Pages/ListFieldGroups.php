<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\FieldGroupResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Builder\Resources\FieldGroupResource;

class ListFieldGroups extends ListRecords
{
    protected static string $resource = FieldGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
