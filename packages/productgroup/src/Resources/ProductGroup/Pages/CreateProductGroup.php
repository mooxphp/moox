<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Resources\ProductGroup\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;
use Moox\ProductGroup\Resources\ProductGroupResource;

class CreateProductGroup extends BaseCreateDraft
{
    protected static string $resource = ProductGroupResource::class;
}
