<?php

declare(strict_types=1);

namespace App\Builder\Resources\LightItemResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;

class EditLightItem extends EditRecord
{
    use BaseInEditPage, SingleSimpleInEditPage;

    protected static string $resource = \App\Builder\Resources\LightItemResource::class;
}
