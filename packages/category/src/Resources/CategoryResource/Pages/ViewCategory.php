<?php

declare(strict_types=1);

namespace Moox\Category\Resources\CategoryResource\Pages;

use Moox\Category\Resources\CategoryResource;
use Moox\Core\Entities\Items\Draft\Pages\BaseViewDraft;

class ViewCategory extends BaseViewDraft
{
    protected static string $resource = CategoryResource::class;
}
