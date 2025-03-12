<?php

declare(strict_types=1);

namespace App\Builder\Resources\SoftDeleteItemResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Core\Traits\Base\BaseInCreatePage;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInCreatePage;
use Moox\Core\Traits\Taxonomy\HasPagesTaxonomy;

class CreateSoftDeleteItem extends CreateRecord
{
    use BaseInCreatePage, HasPagesTaxonomy, SingleSoftDeleteInCreatePage;

    protected static string $resource = \App\Builder\Resources\SoftDeleteItemResource::class;
}
