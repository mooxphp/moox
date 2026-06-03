<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources\ApiConnectionResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Connect\Filament\Resources\ApiConnectionResource;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;

class EditApiConnection extends EditRecord
{
    use BaseInEditPage, SingleSimpleInEditPage;

    protected static string $resource = ApiConnectionResource::class;
}
