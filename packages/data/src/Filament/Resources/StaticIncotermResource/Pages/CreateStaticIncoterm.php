<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticIncotermResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseCreateStaticRecord;
use Moox\Data\Filament\Resources\StaticIncotermResource;

class CreateStaticIncoterm extends BaseCreateStaticRecord
{
    protected static string $resource = StaticIncotermResource::class;
}
