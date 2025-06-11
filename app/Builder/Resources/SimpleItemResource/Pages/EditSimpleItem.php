<?php

declare(strict_types=1);

namespace App\Builder\Resources\SimpleItemResource\Pages;

use App\Builder\Resources\SimpleItemResource;
use Filament\Resources\Pages\EditRecord;
use Moox\Core\Traits\Base\BaseInEditPage;
use Moox\Core\Traits\Simple\SingleSimpleInEditPage;
use Moox\Core\Traits\Taxonomy\HasPagesTaxonomy;

class EditSimpleItem extends EditRecord
{
    use BaseInEditPage, HasPagesTaxonomy, SingleSimpleInEditPage;

    protected static string $resource = SimpleItemResource::class;
}
