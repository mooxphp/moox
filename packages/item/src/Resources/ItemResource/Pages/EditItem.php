<?php

declare(strict_types=1);

namespace Moox\Item\Resources\ItemResource\Pages;

use Moox\Core\Entities\Items\Item\Pages\BaseEditItem;
use Moox\Item\Resources\ItemResource;

class EditItem extends BaseEditItem
{
    protected static string $resource = ItemResource::class;
}
