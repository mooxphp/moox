<?php

declare(strict_types=1);

namespace Moox\Static\Resources\StaticEntryResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseCreateStaticRecord;
use Moox\Static\Resources\StaticEntryResource;

class CreateStaticEntry extends BaseCreateStaticRecord
{
    protected static string $resource = StaticEntryResource::class;
}
