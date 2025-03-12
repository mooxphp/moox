<?php

declare(strict_types=1);

namespace App\Builder\Resources\SoftDeleteItemResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInEditPage;
use Moox\Core\Traits\Taxonomy\HasPagesTaxonomy;

class EditSoftDeleteItem extends EditRecord
{
    use BaseInEditPage, HasPagesTaxonomy, SingleSoftDeleteInEditPage;

    protected static string $resource = \App\Builder\Resources\SoftDeleteItemResource::class;
}
