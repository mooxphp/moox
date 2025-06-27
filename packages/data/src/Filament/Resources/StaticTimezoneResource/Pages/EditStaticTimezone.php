<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticTimezoneResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;
use Moox\Data\Filament\Resources\StaticTimezoneResource;

class EditStaticTimezone extends EditRecord
{
    use BaseInEditPage, SingleSimpleInEditPage;

    protected static string $resource = StaticTimezoneResource::class;
}
