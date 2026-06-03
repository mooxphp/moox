<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources\ApiLogResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Connect\Filament\Resources\ApiLogResource;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;

class EditApiLog extends EditRecord
{
    use BaseInEditPage, SingleSimpleInEditPage;

    protected static string $resource = ApiLogResource::class;
}
