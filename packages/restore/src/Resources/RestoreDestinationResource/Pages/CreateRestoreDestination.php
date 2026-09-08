<?php

declare(strict_types=1);

namespace Moox\Restore\Resources\RestoreDestinationResource\Pages;

use Moox\Core\Entities\Items\Item\Pages\BaseCreateItem;
use Moox\Restore\Resources\RestoreDestinationResource;

class CreateRestoreDestination extends BaseCreateItem
{
    protected static string $resource = RestoreDestinationResource::class;
}
