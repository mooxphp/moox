<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Resources\ProductGroup\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\ProductGroup\Resources\ProductGroupResource;

class ViewProductGroup extends ViewRecord
{
    protected static string $resource = ProductGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
