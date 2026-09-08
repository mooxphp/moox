<?php

declare(strict_types=1);

namespace Moox\BackupServerUi\Resources\SourceResource\Pages;

use Moox\BackupServerUi\Resources\SourceResource;
use Moox\Core\Entities\Items\Item\Pages\BaseEditItem;

class EditSource extends BaseEditItem
{
    protected static string $resource = SourceResource::class;
}
