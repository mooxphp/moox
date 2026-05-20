<?php

declare(strict_types=1);

namespace Moox\Category\Entities\CategoryResource\Pages;

use Moox\Category\Entities\CategoryTreeResource;
use Moox\Tree\Filament\Pages\TreeIndexListRecords;

class TreeListCategories extends TreeIndexListRecords
{
    protected static string $resource = CategoryTreeResource::class;

    public function mount(): void
    {
        if (! request()->has('tab')) {
            $this->redirect(CategoryTreeResource::getUrl('index', ['tab' => 'all']));
        }

        parent::mount();
    }
}
