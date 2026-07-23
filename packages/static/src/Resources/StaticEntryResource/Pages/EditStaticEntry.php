<?php

declare(strict_types=1);

namespace Moox\Static\Resources\StaticEntryResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseEditStaticRecord;
use Moox\Static\Resources\StaticEntryResource;

class EditStaticEntry extends BaseEditStaticRecord
{
    protected static string $resource = StaticEntryResource::class;
}
