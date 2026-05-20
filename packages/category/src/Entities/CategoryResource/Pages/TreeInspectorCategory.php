<?php

declare(strict_types=1);

namespace Moox\Category\Entities\CategoryResource\Pages;

use Moox\Category\Entities\CategoryResource;
use Moox\Tree\Filament\Concerns\RendersAsTreeIndexInspector;

class TreeInspectorCategory extends EditCategory
{
    use RendersAsTreeIndexInspector;

    protected static string $resource = CategoryResource::class;
}
