<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Resources\LocalizationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;

class EditLocalization extends EditRecord
{
    use BaseInEditPage, SingleSimpleInEditPage;

    protected static string $resource = \Moox\DataLanguages\Resources\LocalizationResource::class;
}
