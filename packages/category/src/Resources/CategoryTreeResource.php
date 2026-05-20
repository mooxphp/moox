<?php

declare(strict_types=1);

namespace Moox\Category\Resources;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Moox\Category\Models\Category;
use Moox\Category\Resources\CategoryResource as MooxCategoryResource;
use Moox\Category\Resources\CategoryResource\Pages\TreeInspectorCategory;
use Moox\Category\Resources\CategoryResource\Pages\TreeListCategories;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Contracts\ConfiguresTreeIndex;

class CategoryTreeResource extends MooxCategoryResource implements ConfiguresTreeIndex
{
    public static function getTreeIndexListPage(): string
    {
        return TreeListCategories::class;
    }

    public static function treeIndex(): TreeIndexConfiguration
    {
        return TreeIndexConfiguration::make(Category::class)
            ->labelColumn('title')
            ->labelColumnQueryable(false)
            ->nestedSet()
            ->sortColumn('_lft')
            ->reorderable(true)
            ->inspectorPage(TreeInspectorCategory::class)
            ->modifyQuery(fn (Builder $query): Builder => static::getEloquentQuery())
            ->labels(
                treeHeading: 'Kategorien',
                treeSubheading: 'Baum',
                inspectorHeading: 'Kategorie',
                createRootLabel: 'Neue Kategorie',
                createChildLabel: 'Unterkategorie',
                newRecordLabel: 'Neue Kategorie',
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => TreeListCategories::route('/'),
            'tree-inspector' => TreeInspectorCategory::route('/{record}/tree-inspector'),
            ...Arr::except(parent::getPages(), ['index']),
        ];
    }
}
