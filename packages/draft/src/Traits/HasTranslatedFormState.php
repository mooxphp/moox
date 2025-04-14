<?php

namespace Moox\Draft\Traits;

class DraftResource extends BaseDraftResource
{
    use HasResourceTaxonomy, HasTranslatedFormState;

    // ... other existing code ...

    public static function form(Form $form): Form
    {
        $taxonomyFields = static::getTaxonomyFields();

        $schema = [
            Grid::make(2)
                ->schema([
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    MediaPicker::make('image')
                                        ->label(__('core::core.image')),
                                    static::translateTitleWithSlug(
                                        TitleWithSlugInput::make(
                                            fieldTitle: 'title',
                                            fieldSlug: 'slug',
                                            slugRuleUniqueParameters: [
                                                'modifyRuleUsing' => function (Unique $rule) {
                                                    return $rule
                                                        ->where('locale', request()->query('lang', app()->getLocale()))
                                                        ->where(function ($query) {
                                                            $query->whereNull('draft_translations.draft_id')
                                                                ->orWhere('draft_translations.draft_id', request()->route('record'));
                                                        });
                                                },
                                                'table' => 'draft_translations',
                                                'column' => 'slug',
                                            ]
                                        )
                                    ),
                                    Toggle::make('is_active')
                                        ->label('Active'),
                                    static::translateField(
                                        RichEditor::make('description')
                                            ->label('Description')
                                    ),
                                    static::translateField(
                                        MarkdownEditor::make('content')
                                            ->label('Content')
                                    ),
                                    KeyValue::make('data')
                                        ->label('Data (JSON)'),
                                ]),
                            // ... rest of your form schema
                        ]),
                    // ... rest of your grid schema
                ]),
            // ... rest of your schema
        ];

        return $form->schema($schema);
    }

    // Update your afterSave method to handle all translatable fields
    public static function afterSave(Draft $record, array $data): void
    {
        if (isset($data['_current_locale'])) {
            $translation = $record->translateOrNew($data['_current_locale']);

            // Get all translatable fields from the model
            foreach ($record->translatedAttributes as $field) {
                if (isset($data[$field])) {
                    $translation->$field = $data[$field];
                }
            }

            $record->translations()->save($translation);
        }
    }

    // Update afterCreate similarly
    public static function afterCreate(Draft $record, array $data): void
    {
        if (isset($data['_current_locale'])) {
            $translation = $record->translateOrNew($data['_current_locale']);

            foreach ($record->translatedAttributes as $field) {
                if (isset($data[$field])) {
                    $translation->$field = $data[$field];
                }
            }

            $translation->author_id = auth()->id();
            $record->translations()->save($translation);
        }
    }
}
