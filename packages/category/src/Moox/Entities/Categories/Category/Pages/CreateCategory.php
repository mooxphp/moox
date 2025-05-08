<?php

declare(strict_types=1);

namespace Moox\Category\Moox\Entities\Categories\Category\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;
use Moox\Category\Moox\Entities\Categories\Category\CategoryResource;

class CreateCategory extends BaseCreateDraft
{
    protected static string $resource = CategoryResource::class;

    protected function hasFormActionsInPanel(): bool
    {
        return false;
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
