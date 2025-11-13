<?php

namespace Moox\News\Moox\Entities\News\News;

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
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\News\Models\News;
use Moox\Slug\Forms\Components\TitleWithSlugInput;

class NewsResource extends BaseDraftResource
{
    use HasResourceTabs;
    use HasResourceTaxonomy;

    protected static ?string $model = News::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-newspaper';

    public static function getModelLabel(): string
    {
        return config('news.resources.news.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('news.resources.news.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('news.resources.news.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('news.resources.news.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('news.navigation_group');
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
                                urlPathEntityType: 'news',
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
                                    'table' => 'news_translations',
                                    'column' => 'slug',
                                ]
                            ),
                            MediaPicker::make('image')
                                ->label(__('core::core.image')),
                            Toggle::make('is_active')
                                ->label(__('core::core.active')),
                            MarkdownEditor::make('excerpt')
                                ->label(__('core::core.excerpt'))
                                ->rules(config('news.rules.excerpt')),
                            RichEditor::make('description')
                                ->label(__('core::core.description')),
                            MarkdownEditor::make('content')
                                ->label(__('core::core.content')),
                            MediaPicker::make('gallery')
                                ->label(__('core::core.gallery')),
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
                    ->label(__('core::core.active'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('excerpt')
                    ->label(__('core::core.excerpt'))
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->label(__('core::core.description'))
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('content')
                    ->label(__('core::core.content'))
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('author.name')
                    ->label(__('core::core.author'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->label(__('core::core.type'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('published_at')
                    ->label(__('core::core.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                ColorColumn::make('color')
                    ->label(__('core::core.color'))
                    ->toggleable(),
                TextColumn::make('uuid')
                    ->label(__('core::core.uuid'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ulid')
                    ->label(__('core::core.ulid'))
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
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
            'view' => Pages\ViewNews::route('/{record}'),
        ];
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }
}
