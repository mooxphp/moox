<?php

declare(strict_types=1);

namespace Moox\Builder\Presets;

use Moox\Builder\Blocks\Features\SimpleStatus;
use Moox\Builder\Blocks\Features\SimpleType;
use Moox\Builder\Blocks\Features\Tabs;
use Moox\Builder\Blocks\Features\Taxonomy;
use Moox\Builder\Blocks\Filament\TextArea;
use Moox\Builder\Blocks\Moox\TitleWithSlug;
use Moox\Builder\Blocks\Sections\AddressSection;
use Moox\Builder\Blocks\Singles\SoftDelete;

class SoftDeleteItemPreset extends AbstractPreset
{
    protected function initializePreset(): void
    {
        $this->blocks = [
            new TitleWithSlug(
                titleFieldName: 'title',
                slugFieldName: 'slug',
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
            new SimpleType(
                enum: ['Post', 'Page'],
            ),
            new SimpleStatus(
                enum: ['Probably', 'Never', 'Done', 'Maybe'],
            ),
            new SoftDelete,
        ];
    }
}
