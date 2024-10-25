<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\SimpleItemResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Builder\Resources\SimpleItemResource;

class CreateSimpleItem extends CreateRecord
{
    protected static string $resource = SimpleItemResource::class;
}
