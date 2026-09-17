<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Resources\VeraPdfValidationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\VeraPdf\Resources\VeraPdfValidationResource;

final class ListVeraPdfValidations extends ListRecords
{
    use BaseInListPage;

    protected static string $resource = VeraPdfValidationResource::class;
}
