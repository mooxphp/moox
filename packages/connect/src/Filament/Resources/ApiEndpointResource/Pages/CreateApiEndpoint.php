<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources\ApiEndpointResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;

class CreateApiEndpoint extends CreateRecord
{
    use BaseInCreatePage, SingleSimpleInCreatePage;

    protected static string $resource = \Moox\Connect\Filament\Resources\ApiEndpointResource::class;
}
