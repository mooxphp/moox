<?php

declare(strict_types=1);

namespace App\Locale\Resources\StaticTimezoneResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;

class CreateStaticTimezone extends CreateRecord
{
    use BaseInCreatePage, SingleSimpleInCreatePage;

    protected static string $resource = \App\Locale\Resources\StaticTimezoneResource::class;
}
