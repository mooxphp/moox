<?php

declare(strict_types=1);

namespace Moox\Item\Resources\ItemResource\Pages;

use Moox\Core\Entities\Items\Item\Pages\BaseCreateItem;
use Moox\Item\Resources\ItemResource;

class CreateItem extends BaseCreateItem
{
    protected static string $resource = ItemResource::class;
}
