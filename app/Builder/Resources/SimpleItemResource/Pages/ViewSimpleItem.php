<?php

declare(strict_types=1);

namespace App\Builder\Resources\SimpleItemResource\Pages;

use App\Builder\Resources\SimpleItemResource;
use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\Simple\SingleSimpleInViewPage;
use Moox\Core\Traits\Taxonomy\HasPagesTaxonomy;

class ViewSimpleItem extends ViewRecord
{
    use BaseInViewPage, HasPagesTaxonomy, SingleSimpleInViewPage;

    protected static string $resource = SimpleItemResource::class;
}
