<?php

declare(strict_types=1);

namespace Moox\Salutation\Resources\HonorificTitle\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Salutation\Resources\HonorificTitleResource;

class CreateHonorificTitle extends CreateRecord
{
    protected static string $resource = HonorificTitleResource::class;
}
