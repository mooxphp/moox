<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticLanguageResource\Pages;

use Moox\Data\Filament\Resources\StaticLanguageResource;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;

class ListStaticLanguages extends ListRecords
{
    use BaseInListPage, SingleSimpleInListPage;

    protected static string $resource = StaticLanguageResource::class;
}
