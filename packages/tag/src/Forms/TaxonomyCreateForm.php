<?php

namespace Moox\Tag\Forms;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
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
                fieldPermalink: 'permalink',
                urlPathEntityType: 'tags',
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
                    'table' => 'tag_translations',
                    'column' => 'slug',
                ]
            ),
            MarkdownEditor::make('content')
                ->label(__('core::core.content')),
            Grid::make(2)
                ->schema([
                    ColorPicker::make('color'),
                    TextInput::make('weight'),
                ]),
        ];
    }
}
