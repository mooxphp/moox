<?php

namespace Moox\Core\Entities\Items\Record\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\CanResolveResourceClass;

abstract class BaseListRecords extends ListRecords
{
    use CanResolveResourceClass;
}
