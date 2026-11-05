<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Resources\ProductGroup\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\ProductGroup\Resources\ProductGroupResource;

class EditProductGroup extends EditRecord
{
    protected static string $resource = ProductGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
