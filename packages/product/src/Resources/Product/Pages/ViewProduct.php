<?php

declare(strict_types=1);

namespace Moox\Product\Resources\Product\Pages;

use Moox\Core\Entities\Items\Draft\Pages\BaseViewDraft;
use Moox\Product\Resources\ProductResource;

class ViewProduct extends BaseViewDraft {
    protected static string $resource = ProductResource::class;
}
