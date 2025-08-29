<?php

declare(strict_types=1);

namespace Moox\Category\Moox\Entities\Categories\Category;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use Moox\Category\Models\Category;
use Moox\Category\Moox\Entities\Categories\Category\Resources\CategoryResource\Pages\CreateCategory;
use Moox\Category\Moox\Entities\Categories\Category\Resources\CategoryResource\Pages\EditCategory;
use Moox\Category\Moox\Entities\Categories\Category\Resources\CategoryResource\Pages\ListCategories;
use Moox\Category\Moox\Entities\Categories\Category\Resources\CategoryResource\Pages\ViewCategory;
use Moox\Core\Entities\Items\Draft\BaseDraftResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\Slug\Forms\Components\TitleWithSlugInput;
use Override;

class CategoryResource extends BaseDraftResource
{
    use HasResourceTabs;

    protected static ?string $model = Category::class;

    protected static ?string $currentTab = null;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-category';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TitleWithSlugInput::make(
                                    fieldTitle: 'title',
                                    fieldSlug: 'slug',
                                    fieldPermalink: 'permalink',
                                    urlPathEntityType: 'categories',
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
                                        },
                                        'table' => 'category_translations',
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
                                    ->enableBranchNode()
                                    ->visible(fn () => Category::count() > 0),
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
                                Section::make()
                                    ->schema([
                                        TextInput::make('weight')->numeric(),
                                        TextInput::make('count')
                                            ->disabled()
                                            ->visible(fn ($livewire, $record): bool => ($record && $livewire instanceof EditCategory) || ($record && $livewire instanceof ViewCategory)),
                                    ]),
                                Section::make('')
                                    ->schema([
                                        static::getTranslationStatusSelect(),
                                        static::getPublishDateField(),
                                        static::getUnpublishDateField(),
                                    ]),
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
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => static::getEloquentQuery())
            ->defaultSort('_lft', 'asc')
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
                TextColumn::make('level')
                    ->label(__('category::fields.level'))
                    ->getStateUsing(fn (Category $record): int => $record->ancestors->count() + 1)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('children_count')
                    ->label(__('category::fields.children_count'))
                    ->counts('children')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('parent.title')
                    ->label(__('category::fields.parent'))
                    ->sortable(),
                TextColumn::make('count')
                    ->label(__('core::core.count'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('weight')
                    ->label(__('category::fields.weight'))
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
                static::getTranslationStatusFilter(),
                SelectFilter::make('parent_id')
                    ->label(__('category::fields.parent'))
                    ->relationship('parent', 'title', fn ($query) => $query->has('children'))
                    ->searchable(),
                SelectFilter::make('children_count')
                    ->label(__('category::fields.children_count'))
                    ->options([
                        '0' => '0',
                        '1-5' => '1-5',
                        '6-10' => '6-10',
                        '10+' => '10+',
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], function ($query, $option) {
                        switch ($option) {
                            case '0':
                                return $query->doesntHave('children');
                            case '1-5':
                                return $query->has('children', '>=', 1)->has('children', '<=', 5);
                            case '6-10':
                                return $query->has('children', '>=', 6)->has('children', '<=', 10);
                            case '10+':
                                return $query->has('children', '>', 10);
                        }
                    })),
                SelectFilter::make('depth')
                    ->label(__('category::fields.level'))
                    ->options(fn (): array => array_combine(range(1, 5), range(1, 5)))
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], function ($query, $depth): void {
                        $query->whereIn('id', function ($subquery) use ($depth): void {
                            $subquery->select('id')
                                ->from('categories as c')
                                ->whereRaw('(SELECT COUNT(*) FROM categories as ancestors WHERE ancestors._lft < c._lft AND ancestors._rgt > c._rgt) = ?', [$depth - 1]);
                        });
                    })),
            ])
            ->defaultSort('id', 'asc')
            ->deferFilters(false)
            ->persistFiltersInSession();
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'edit' => EditCategory::route('/{record}/edit'),
            'create' => CreateCategory::route('/create'),
            'view' => ViewCategory::route('/{record}'),
        ];
    }

    #[Override]
    public static function getWidgets(): array
    {
        return [
        ];
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return config('category.resources.category.single');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return config('category.resources.category.plural');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return config('category.resources.category.plural');
    }

    #[Override]
    public static function getBreadcrumb(): string
    {
        return config('category.resources.category.single');
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return config('category.navigation_group');
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }
}
