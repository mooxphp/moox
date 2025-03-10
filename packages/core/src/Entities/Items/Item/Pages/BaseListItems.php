<?php

namespace Moox\Core\Entities\Items\Item\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\ResolveResourceClass;

abstract class BaseListItems extends ListRecords
{
    use ResolveResourceClass;
}
