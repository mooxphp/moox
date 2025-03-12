<?php

declare(strict_types=1);

namespace App\Builder\Resources\SoftDeleteItemResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Core\Traits\Base\BaseInViewPage;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInViewPage;
use Moox\Core\Traits\Taxonomy\HasPagesTaxonomy;

class ViewSoftDeleteItem extends ViewRecord
{
    use BaseInViewPage, HasPagesTaxonomy, SingleSoftDeleteInViewPage;

    protected static string $resource = \App\Builder\Resources\SoftDeleteItemResource::class;
}
