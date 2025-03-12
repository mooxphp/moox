<?php

declare(strict_types=1);

namespace App\Builder\Resources\TranslateItemResource\Pages;

use App\Builder\Resources\TranslateItemResource;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;

class ListTranslateItems extends ListRecords
{
    use BaseInListPage;
    use SingleSimpleInListPage;
    protected static string $resource = TranslateItemResource::class;
}
