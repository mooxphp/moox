<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Resources\LocalizationResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;
use Moox\Localization\Filament\Resources\LocalizationResource;

class CreateLocalization extends CreateRecord
{
    use BaseInCreatePage, SingleSimpleInCreatePage;

    protected static string $resource = LocalizationResource::class;
}
