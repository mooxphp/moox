<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticLanguageResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Data\Filament\Resources\StaticLanguageResource;

class ListStaticLanguages extends BaseListRecords
{
    use BaseInListPage;

    protected static string $resource = StaticLanguageResource::class;
}
