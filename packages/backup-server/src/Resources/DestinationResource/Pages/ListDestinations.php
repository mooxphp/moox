<?php

declare(strict_types=1);

namespace Moox\BackupServerUi\Resources\DestinationResource\Pages;

use Moox\BackupServerUi\Resources\DestinationResource;
use Moox\Core\Entities\Items\Item\Pages\BaseListItems;

class ListDestinations extends BaseListItems
{
    protected static string $resource = DestinationResource::class;
}
