<?php

declare(strict_types=1);

namespace Moox\BackupServerUi\Resources\DestinationResource\Pages;

use Moox\BackupServerUi\Resources\DestinationResource;
use Moox\Core\Entities\Items\Item\Pages\BaseEditItem;

class EditDestination extends BaseEditItem
{
    protected static string $resource = DestinationResource::class;

    public function getTitle(): string
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return 'Edit '.$this->getRecordTitle().' destination';
    }
}
