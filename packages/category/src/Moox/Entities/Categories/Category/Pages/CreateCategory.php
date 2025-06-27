<?php

declare(strict_types=1);

namespace Moox\Category\Moox\Entities\Categories\Category\Pages;

use Moox\Category\Moox\Entities\Categories\Category\CategoryResource;
use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;

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
