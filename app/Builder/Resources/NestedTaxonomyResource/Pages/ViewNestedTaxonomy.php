<?php

declare(strict_types=1);

namespace App\Builder\Resources\NestedTaxonomyResource\Pages;

use App\Builder\Resources\NestedTaxonomyResource;
use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;

class ViewNestedTaxonomy extends ViewRecord
{
    use BaseInViewPage;
    use SingleSimpleInViewPage;

    protected static string $resource = NestedTaxonomyResource::class;
}
