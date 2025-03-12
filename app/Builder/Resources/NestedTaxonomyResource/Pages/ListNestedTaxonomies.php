<?php

declare(strict_types=1);

namespace App\Builder\Resources\NestedTaxonomyResource\Pages;

use App\Builder\Resources\NestedTaxonomyResource;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\Core\Traits\Simple\SingleSimpleInListPage;

class ListNestedTaxonomies extends ListRecords
{
    use BaseInListPage;
    use SingleSimpleInListPage;

    protected static string $resource = NestedTaxonomyResource::class;
}
