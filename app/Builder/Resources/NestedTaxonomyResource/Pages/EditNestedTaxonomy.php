<?php

declare(strict_types=1);

namespace App\Builder\Resources\NestedTaxonomyResource\Pages;

use App\Builder\Resources\NestedTaxonomyResource;
use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;

class EditNestedTaxonomy extends EditRecord
{
    use BaseInEditPage;
    use SingleSimpleInEditPage;

    protected static string $resource = NestedTaxonomyResource::class;
}
