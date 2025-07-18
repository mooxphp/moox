<?php

namespace Moox\Category\Moox\Entities\Categories\Category\Forms;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use Moox\Slug\Forms\Components\TitleWithSlugInput;

class TaxonomyCreateForm
{
    public static function getSchema(): array
    {
        return [
            TitleWithSlugInput::make(
                fieldTitle: 'title',
                fieldSlug: 'slug',
                slugRuleUniqueParameters: [
                    'modifyRuleUsing' => function (Unique $rule, $record, $livewire) {
                        $locale = $livewire->lang;
                        if ($record) {
                            $rule->where('locale', $locale);
                            $existingTranslation = $record->translations()
                                ->where('locale', $locale)
                                ->first();
                            if ($existingTranslation) {
                                $rule->ignore($existingTranslation->id);
                            }
                        } else {
                            $rule->where('locale', $locale);
                        }

                        return $rule;
                    },
                    'table' => 'category_translations',
                    'column' => 'slug',
                ]
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
