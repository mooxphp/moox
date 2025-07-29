<?php

declare(strict_types=1);

namespace Moox\Category\Moox\Entities\Categories\Category\Resources\CategoryResource\Pages;

use Override;

use Filament\Resources\Pages\CreateRecord;
use Moox\Category\Moox\Entities\Categories\Category\CategoryResource;

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
