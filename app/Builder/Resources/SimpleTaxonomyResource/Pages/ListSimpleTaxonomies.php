<?php

declare(strict_types=1);

namespace App\Builder\Resources\SimpleTaxonomyResource\Pages;

use App\Builder\Resources\SimpleTaxonomyResource;
use Filament\Resources\Pages\ListRecords;

class ListSimpleTaxonomies extends ListRecords
{
    protected static string $resource = SimpleTaxonomyResource::class;
}
