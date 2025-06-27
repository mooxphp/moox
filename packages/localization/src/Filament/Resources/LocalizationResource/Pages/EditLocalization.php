<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Resources\LocalizationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;
use Moox\Localization\Filament\Resources\LocalizationResource;

class EditLocalization extends EditRecord
{
    use BaseInEditPage, SingleSimpleInEditPage;

    protected static string $resource = LocalizationResource::class;
}
