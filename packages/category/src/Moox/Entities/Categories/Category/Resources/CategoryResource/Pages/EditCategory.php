<?php

declare(strict_types=1);

namespace Moox\Category\Moox\Entities\Categories\Category\Resources\CategoryResource\Pages;

use Moox\Category\Moox\Entities\Categories\Category\CategoryResource;
use Moox\Core\Entities\Items\Draft\Pages\BaseEditDraft;
use Override;

class EditCategory extends BaseEditDraft
{
    protected static string $resource = CategoryResource::class;

    #[Override]
    protected function getFormActions(): array
    {
        return [];
    }
}
