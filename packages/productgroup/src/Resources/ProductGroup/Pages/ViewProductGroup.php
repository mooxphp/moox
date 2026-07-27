<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Resources\ProductGroup\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseViewDraft;
use Moox\ProductGroup\Resources\ProductGroupResource;

class ViewProductGroup extends BaseViewDraft
{
    protected static string $resource = ProductGroupResource::class;
}
