<?php

declare(strict_types=1);

namespace App\Builder\Resources\SimpleTaxonomyResource\Pages;

use App\Builder\Resources\SimpleTaxonomyResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSimpleTaxonomy extends ViewRecord
{
    protected static string $resource = SimpleTaxonomyResource::class;
}
