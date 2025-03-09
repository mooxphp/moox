<?php

namespace Moox\Core\Entities\Items\Item\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\ResolveResourceClass;

abstract class ItemEditPage extends EditRecord
{
    use ResolveResourceClass;
}
