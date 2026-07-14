<?php

declare(strict_types=1);

namespace Moox\Page\Resources;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Moox\BlockEditor\Forms\Components\BlockEditor;
use Moox\Core\Entities\Items\Draft\BaseDraftResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Core\Traits\Taxonomy\HasResourceTaxonomy;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\Page\Models\Page;
use Moox\Page\Resources\PageResource\Pages\CreatePage;
use Moox\Page\Resources\PageResource\Pages\EditPage;
use Moox\Page\Resources\PageResource\Pages\ListPages;
use Moox\Page\Resources\PageResource\Pages\ViewPage;
use Moox\Page\Support\PageModels;
use Moox\Slug\Forms\Components\TitleWithSlugInput;

class PageResource extends BaseDraftResource
{
    use HasResourceTabs;
    use HasResourceTaxonomy;

    protected static ?string $model = Page::class;

    public static function getModel(): string
    {
        return PageModels::page();
    }

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-description';

    public static function getModelLabel(): string
    {
        return config('page.resources.page.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('page.resources.page.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('page.resources.page.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('page.resources.page.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('page.navigation_group');
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
                                urlPathEntityType: null,
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
                                    'table' => 'page_translations',
                                    'column' => 'slug',
                                ]
                            ),
                            MediaPicker::make('image')
                                ->label(__('core::core.image')),
                            Toggle::make('is_active')
                                ->label(__('core::core.active')),
                            static::getHomepageToggle(),
                            RichEditor::make('description')
                                ->label(__('core::core.description')),
                            BlockEditor::make('content')
                                ->label(__('core::core.content'))
                                ->columnSpanFull(),
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
                                    static::getLayoutSelect(),
                                    static::getTranslationStatusSelect(),
                                    static::getPublishDateField(),
                                    static::getUnpublishDateField(),
                                ]),
                            Section::make('')
                                ->schema($taxonomyFields),
                            Section::make('')
                                ->schema([
                                    static::getAuthorSelect(),
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
                TextColumn::make('layout')
                    ->label('Layout')
                    ->formatStateUsing(fn (?string $state): string => Page::layoutOptions()[$state] ?? (string) $state)
                    ->sortable(),
                TranslationColumn::make('translations.locale'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
                IconColumn::make('is_startpage')
                    ->boolean()
                    ->label('Startseite')
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
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
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
                SelectFilter::make('layout')
                    ->label('Layout')
                    ->options(Page::layoutOptions()),
                ...static::getTaxonomyFilters(),
                static::getLocaleFilter(),
            ])->deferFilters(false)
            ->persistFiltersInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
            'view' => ViewPage::route('/{record}'),
        ];
    }

    protected static function getLayoutSelect(): Select
    {
        return Select::make('layout')
            ->label('Layout')
            ->options(Page::layoutOptions())
            ->default('default')
            ->required();
    }

    protected static function getHomepageToggle(): Toggle
    {
        return Toggle::make('is_startpage')
            ->label('Startseite')
            ->helperText('Es kann immer nur eine Seite als Startseite festgelegt werden.');
    }
}
