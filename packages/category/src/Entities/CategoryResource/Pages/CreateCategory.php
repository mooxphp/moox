<?php

declare(strict_types=1);

namespace Moox\Category\Entities\CategoryResource\Pages;

use Moox\Category\Entities\CategoryResource;
use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;

class CreateCategory extends BaseCreateDraft
{
    protected static string $resource = CategoryResource::class;
}
