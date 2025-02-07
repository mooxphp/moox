<?php

declare(strict_types=1);

namespace Moox\Category\Resources\CategoryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Category\Resources\CategoryResource;
use Override;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected function hasFormActionsInPanel(): bool
    {
        return false;
    }

    #[Override]
    protected function getFormActions(): array
    {
        return [];
    }
}
