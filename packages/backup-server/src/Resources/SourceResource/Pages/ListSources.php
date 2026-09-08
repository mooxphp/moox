<?php

declare(strict_types=1);

namespace Moox\BackupServerUi\Resources\SourceResource\Pages;

use Moox\BackupServerUi\Resources\SourceResource;
use Moox\Core\Entities\Items\Item\Pages\BaseListItems;

class ListSources extends BaseListItems
{
    protected static string $resource = SourceResource::class;
}
