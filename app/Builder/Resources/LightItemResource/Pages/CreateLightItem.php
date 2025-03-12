<?php

declare(strict_types=1);

namespace App\Builder\Resources\LightItemResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;

class CreateLightItem extends CreateRecord
{
    use BaseInCreatePage, SingleSimpleInCreatePage;

    protected static string $resource = \App\Builder\Resources\LightItemResource::class;
}
