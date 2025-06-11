<?php

declare(strict_types=1);

namespace App\Builder\Resources\LightItemResource\Pages;

use App\Builder\Resources\LightItemResource;
use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;

class CreateLightItem extends CreateRecord
{
    use BaseInCreatePage, SingleSimpleInCreatePage;

    protected static string $resource = LightItemResource::class;
}
