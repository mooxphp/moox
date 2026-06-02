<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources\ApiLogResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Connect\Filament\Resources\ApiLogResource;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;

class CreateApiLog extends CreateRecord
{
    use BaseInCreatePage, SingleSimpleInCreatePage;

    protected static string $resource = ApiLogResource::class;
}
