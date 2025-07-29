<?php

declare(strict_types=1);

namespace Moox\Category\Moox\Entities\Categories\Category\Resources\CategoryResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Category\Moox\Entities\Categories\Category\CategoryResource;
use Override;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction::make(),
        ];
    }

    #[Override]
    protected function getFormActions(): array
    {
        return [];
    }
}
