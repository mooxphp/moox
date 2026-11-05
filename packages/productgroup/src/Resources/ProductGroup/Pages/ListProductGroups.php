<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Resources\ProductGroup\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\ProductGroup\Resources\ProductGroupResource;

class ListProductGroups extends ListRecords
{
    protected static string $resource = ProductGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
