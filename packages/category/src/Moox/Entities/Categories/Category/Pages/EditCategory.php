<?php

declare(strict_types=1);

namespace Moox\Category\Moox\Entities\Categories\Category\Pages;

use Moox\Category\Moox\Entities\Categories\Category\CategoryResource;
use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;

class EditCategory extends BaseEditDraft
{
    protected static string $resource = CategoryResource::class;

    // #[Override]
    protected function getFormActions(): array
    {
        return [];
    }
}
