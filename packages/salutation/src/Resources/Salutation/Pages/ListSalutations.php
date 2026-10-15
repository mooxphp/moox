<?php

declare(strict_types=1);

namespace Moox\Salutation\Resources\Salutation\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Salutation\Resources\SalutationResource;

class ListSalutations extends ListRecords
{
    protected static string $resource = SalutationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
