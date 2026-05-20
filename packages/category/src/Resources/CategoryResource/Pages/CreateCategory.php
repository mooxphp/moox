<?php

declare(strict_types=1);

namespace Moox\Category\Resources\CategoryResource\Pages;

use Moox\Category\Resources\CategoryResource;
use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;

class CreateCategory extends BaseCreateDraft
{
    protected static string $resource = CategoryResource::class;
}
