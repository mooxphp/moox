<?php

namespace Moox\Core\Entities\Items\Record\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\ResolveResourceClass;

abstract class BaseEditRecord extends EditRecord
{
    use ResolveResourceClass;
}
