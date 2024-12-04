<?php

declare(strict_types=1);

namespace Moox\Builder\Presets;

use Moox\Builder\Blocks\Sections\AddressSection;
use Moox\Builder\Blocks\Simple;
use Moox\Builder\Blocks\SimpleStatus;
use Moox\Builder\Blocks\Tabs;
use Moox\Builder\Blocks\Taxonomy;
use Moox\Builder\Blocks\Text;
use Moox\Builder\Blocks\TextArea;

class SimpleItemPreset extends AbstractPreset
{
    protected function initializePreset(): void
    {
        $this->blocks = [
            new Simple,
            new Text(
                name: 'title',
                label: 'Title',
                description: 'The title of the item',
                length: 255,
                nullable: false,
                unique: true,
                searchable: true,
                sortable: true,
                toggleable: true,
                filterable: true,
            ),
            new TextArea(
                name: 'content',
                label: 'Content',
                description: 'The content of the item'
            ),
            new Tabs,
            new Taxonomy(
                single: 'Category',
                plural: 'Categories',
                model: '\Moox\Category\Models\Category::class',
                table: 'categorizables',
                relationship: 'categorizable',
                foreignKey: 'categorizable_id',
                relatedKey: 'category_id',
                createForm: '\Moox\Category\Forms\TaxonomyCreateForm::class',
                nested: true,
            ),
            new Taxonomy(
                single: 'Tag',
                plural: 'Tags',
                model: '\Moox\Tag\Models\Tag::class',
                table: 'taggables',
                relationship: 'taggable',
                foreignKey: 'taggable_id',
                relatedKey: 'tag_id',
                createForm: '\Moox\Tag\Forms\TaxonomyCreateForm::class',
            ),
            new AddressSection,
            new SimpleStatus,
        ];
    }
}
