<?php

declare(strict_types=1);

namespace Moox\Product\Resources\Product\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseCreateDraft;
use Moox\Product\Resources\ProductResource;

class CreateProduct extends BaseCreateDraft {
    protected static string $resource = ProductResource::class;
}
