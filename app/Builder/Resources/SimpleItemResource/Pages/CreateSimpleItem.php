<?php

declare(strict_types=1);

namespace App\Builder\Resources\SimpleItemResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\Simple\SingleSimpleInCreatePage;
use Moox\Core\Traits\Taxonomy\HasPagesTaxonomy;

class CreateSimpleItem extends CreateRecord
{
    use BaseInCreatePage, HasPagesTaxonomy, SingleSimpleInCreatePage;

    protected static string $resource = \App\Builder\Resources\SimpleItemResource::class;
}
