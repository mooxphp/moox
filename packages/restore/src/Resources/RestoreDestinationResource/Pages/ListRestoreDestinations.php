<?php

declare(strict_types=1);

namespace Moox\Restore\Resources\RestoreDestinationResource\Pages;

use Moox\Core\Entities\Items\Item\Pages\BaseListItems;
use Moox\Restore\Resources\RestoreDestinationResource;

class ListRestoreDestinations extends BaseListItems
{
    protected static string $resource = RestoreDestinationResource::class;
}
