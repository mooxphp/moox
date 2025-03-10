<?php

namespace Moox\Core\Entities\Items\Item\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\ResolveResourceClass;

abstract class BaseEditItem extends EditRecord
{
    use ResolveResourceClass;
}
