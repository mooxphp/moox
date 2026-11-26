<?php

declare(strict_types=1);

namespace Moox\ProductCategory\Resources\ProductCategory\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\ProductCategory\Resources\ProductCategoryResource;

class ViewProductCategory extends ViewRecord
{
    protected static string $resource = ProductCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
