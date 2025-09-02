<?php

namespace Moox\Draft\Moox\Entities\Drafts\Draft;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Moox\Core\Entities\Items\Draft\BaseDraftResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Core\Traits\Taxonomy\HasResourceTaxonomy;
use Moox\Draft\Models\Draft;
use Moox\Draft\Moox\Entities\Drafts\Draft\Pages\CreateDraft;
use Moox\Draft\Moox\Entities\Drafts\Draft\Pages\EditDraft;
use Moox\Draft\Moox\Entities\Drafts\Draft\Pages\ListDrafts;
use Moox\Draft\Moox\Entities\Drafts\Draft\Pages\ViewDraft;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\Slug\Forms\Components\TitleWithSlugInput;

class DraftResource extends BaseDraftResource
{
    use HasResourceTabs;
    use HasResourceTaxonomy;

    protected static ?string $model = Draft::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-description';

    public static function getModelLabel(): string
    {
        return config('draft.resources.draft.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('draft.resources.draft.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('draft.resources.draft.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('draft.resources.draft.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('draft.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        $taxonomyFields = static::getTaxonomyFields();

        $schema = [
            Grid::make()
                ->schema([
                    Section::make()
                        ->schema([
                            TitleWithSlugInput::make(
                                fieldTitle: 'title',
                                fieldSlug: 'slug',
                                fieldPermalink: 'permalink',
                                urlPathEntityType: 'drafts',
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
                                    'table' => 'draft_translations',
                                    'column' => 'slug',
                                ]
                            ),
                            MediaPicker::make('image')
                                ->label(__('core::core.image')),
                            Toggle::make('is_active')
                                ->label(__('core::core.active')),
                            RichEditor::make('description')
                                ->label(__('core::core.description')),
                            MarkdownEditor::make('content')
                                ->label(__('core::core.content')),
                            Grid::make(2)
                                ->schema([
                                    static::getFooterActions()->columnSpan(1),
                                ]),
                        ])->columnSpan(2),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make('')
                                ->schema([
                                    static::getTypeSelect(),
                                    static::getTranslationStatusSelect(),
                                    static::getPublishDateField(),
                                    static::getUnpublishDateField(),
                                ]),
                            Section::make('')
                                ->schema($taxonomyFields),
                            Section::make('')
                                ->schema([
                                    static::getAuthorSelect(),
                                    DateTimePicker::make('due_at')
                                        ->label(__('core::core.due')),
                                    ColorPicker::make('color')
                                        ->label(__('core::core.color')),
                                ]),
                            Section::make('')
                                ->schema([
                                    ...static::getStandardCopyableFields(),
                                    Section::make('')
                                        ->schema([
                                            ...static::getStandardTimestampFields(),
                                        ]),
                                ])
                                ->hidden(fn ($record) => $record === null),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ];

        return $form
            ->components($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getTitleColumn(),
                static::getSlugColumn(),
                TranslationColumn::make('translations.locale'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('content')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('author.name')
                    ->label('Author')
                    ->sortable(),
                TextColumn::make('type')
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                ColorColumn::make('color')
                    ->toggleable(),
                TextColumn::make('uuid')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ulid')
                    ->toggleable(isToggledHiddenByDefault: true),
                static::getStatusColumn(),
                ...static::getTaxonomyColumns(),
            ])
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('core::core.active')),
                static::getTranslationStatusFilter(),
                SelectFilter::make('type')
                    ->label(__('core::core.type'))
                    ->options(['Post' => 'Post', 'Page' => 'Page']),
                ...static::getTaxonomyFilters(),
            ])->deferFilters(false)
            ->persistFiltersInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDrafts::route('/'),
            'create' => CreateDraft::route('/create'),
            'edit' => EditDraft::route('/{record}/edit'),
            'view' => ViewDraft::route('/{record}'),
        ];
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }
}
