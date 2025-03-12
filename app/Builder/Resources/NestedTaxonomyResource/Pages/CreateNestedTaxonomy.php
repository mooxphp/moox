<?php

declare(strict_types=1);

namespace App\Builder\Resources\NestedTaxonomyResource\Pages;

use App\Builder\Resources\NestedTaxonomyResource;
use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;

class CreateNestedTaxonomy extends CreateRecord
{
    use BaseInCreatePage;
    use SingleSimpleInCreatePage;
    protected static string $resource = NestedTaxonomyResource::class;
}
