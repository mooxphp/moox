<?php

declare(strict_types=1);

namespace Moox\Item\Resources\ItemResource\Pages;

use Moox\Core\Entities\Items\Item\Pages\BaseViewItem;
use Moox\Item\Resources\ItemResource;

class ViewItem extends BaseViewItem
{
    protected static string $resource = ItemResource::class;
}
