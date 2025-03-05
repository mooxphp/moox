<?php

namespace Moox\Category\Forms;

use Camya\Filament\Forms\Components\TitleWithSlugInput;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class TaxonomyCreateForm
{
    public static function getSchema(): array
    {
        return [
            TitleWithSlugInput::make(
                fieldTitle: 'title',
                fieldSlug: 'slug',
            ),
            FileUpload::make('featured_image_url')
                ->label(__('core::core.featured_image_url')),
            MarkdownEditor::make('content')
                ->label(__('core::core.content')),
            SelectTree::make('parent_id')
                ->relationship(
                    relationship: 'parent',
                    titleAttribute: 'title',
                    parentAttribute: 'parent_id',
                    modifyQueryUsing: fn (Builder $query, $get) => $query->where('id', '!=', $get('id'))
                )
                ->label('Parent Category')
                ->searchable()
                ->disabledOptions(fn ($get): array => [$get('id')])
                ->enableBranchNode(),
            Grid::make(2)
                ->schema([
                    ColorPicker::make('color'),
                    TextInput::make('weight'),
                ]),
        ];
    }
}
