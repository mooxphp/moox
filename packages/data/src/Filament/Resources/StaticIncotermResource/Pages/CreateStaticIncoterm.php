<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticIncotermResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Data\Filament\Resources\StaticIncotermResource;

class CreateStaticIncoterm extends BaseCreateRecord
{
    protected static string $resource = StaticIncotermResource::class;
}
