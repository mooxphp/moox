<?php

declare(strict_types=1);

namespace Moox\Restore\Resources\RestoreDestinationResource\Pages;

use Moox\Core\Entities\Items\Item\Pages\BaseEditItem;
use Moox\Restore\Resources\RestoreDestinationResource;

class EditRestoreDestination extends BaseEditItem
{
    protected static string $resource = RestoreDestinationResource::class;
}
