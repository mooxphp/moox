<?php

namespace Moox\Core\Entities\Items\Draft\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\CanResolveResourceClass;

abstract class BaseListDrafts extends ListRecords
{
    use CanResolveResourceClass;
}
