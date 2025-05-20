<?php

declare(strict_types=1);

namespace Moox\Category\Moox\Entities\Categories\Category;

use Override;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Moox\Category\Models\Category;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Validation\Rules\Unique;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ColorPicker;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Media\Forms\Components\MediaPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Tables\Actions\RestoreBulkAction;
use Moox\Media\Tables\Columns\CustomImageColumn;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Moox\Slug\Forms\Components\TitleWithSlugInput;
use Moox\Core\Entities\Items\Draft\BaseDraftResource;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\Category\Moox\Entities\Categories\Category\Pages\EditCategory;
use Moox\Category\Moox\Entities\Categories\Category\Pages\ViewCategory;


class CategoryResource extends BaseDraftResource
{
    use HasResourceTabs;

    protected static ?string $model = Category::class;

    protected static ?string $currentTab = null;

    protected static ?string $navigationIcon = 'gmdi-category';

    #[Override]
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
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
    
                                                },
                                                'table' => 'category_translations',
                                                'column' => 'slug',
                                            ]
                                        ),
                                      MediaPicker::make('featured_image_url')
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
                                            ->enableBranchNode()
                                            ->visible(fn () => Category::count() > 0),
                                            KeyValue::make('basedata'),
                                            KeyValue::make('data'),
                                            KeyValue::make('data.ArticleGroup'),


                                    ]),
                            ])
                            ->columnSpan(['lg' => 2]),

                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        static::getFormActions(),
                                    ]),
                                Section::make()
                                    ->schema([

                                        ColorPicker::make('color'),
                                        TextInput::make('weight')->numeric(),
                                        TextInput::make('count')
                                            ->disabled()
                                            ->visible(fn ($livewire, $record): bool => ($record && $livewire instanceof EditCategory) || ($record && $livewire instanceof ViewCategory)),
                                        DateTimePicker::make('created_at')
                                            ->disabled()
                                            ->visible(fn ($livewire, $record): bool => ($record && $livewire instanceof EditCategory) || ($record && $livewire instanceof ViewCategory)),
                                        DateTimePicker::make('updated_at')
                                            ->disabled()
                                            ->visible(fn ($livewire, $record): bool => ($record && $livewire instanceof EditCategory) || ($record && $livewire instanceof ViewCategory)),
                                        DateTimePicker::make('deleted_at')
                                            ->disabled()
                                            ->visible(fn ($livewire, $record): bool => $record && $record->trashed() && $livewire instanceof ViewCategory),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 1]),
                    ])
                    ->columns(['lg' => 3]),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        $currentTab = static::getCurrentTab();

        return $table
            ->query(fn (): Builder => static::getEloquentQuery())
            ->defaultSort('_lft', 'asc')
            ->columns([
                TextColumn::make('id')->sortable(),
                CustomImageColumn::make('featured_image_url'),
                TranslationColumn::make('translations.locale'),
                TextColumn::make('modified_title')
                    ->label(__('category::fields.modified_title'))
                    ->getStateUsing(function (Category $record): string {
                        $lang = request()->get('lang');
                        
                        $depth = $record->ancestors->count();
                        $prefix = str_repeat('--', $depth);
                        
                        $title = $lang && $record->hasTranslation($lang) 
                            ? $record->translate($lang)->title 
                            : $record->title;

                        return sprintf('%s %s', $prefix, $title);
                    })
                    ->searchable(),
                   
                TextColumn::make('slug')
                    ->label(__('core::core.slug'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->state(function ($record) {
                        $lang = request()->get('lang');
                        if ($lang && $record->hasTranslation($lang)) {
                            return $record->translate($lang)->slug;
                        }

                        return $record->slug;
                    }),
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('content')
                    ->label(__('core::core.content'))
                    ->sortable()
                    ->limit(30)
                    ->searchable()
                    ->toggleable()
                    ->state(function ($record) {
                        $lang = request()->get('lang');
                        if ($lang && $record->hasTranslation($lang)) {
                            return $record->translate($lang)->content;
                        }

                        return $record->content;
                    }),
                TextColumn::make('count')
                    ->label(__('core::core.count'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('weight')
                    ->label(__('category::fields.weight'))
                    ->sortable()
                    ->toggleable(),
                ColorColumn::make('color')
                    ->label(__('category::fields.color'))
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->actions([
                ViewAction::make(),
                EditAction::make()->hidden(fn (): bool => in_array(static::getCurrentTab(), ['trash', 'deleted']))
            ])
            ->bulkActions([
                DeleteBulkAction::make()->hidden(fn (): bool => in_array($currentTab, ['trash', 'deleted'])),
                RestoreBulkAction::make()->visible(fn (): bool => in_array($currentTab, ['trash', 'deleted'])),
            ])
            ->filters([
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
            ->actions([
                ViewAction::make(),
                EditAction::make()->hidden(fn (): bool => in_array(static::getCurrentTab(), ['trash', 'deleted'])),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->hidden(fn (): bool => in_array($currentTab, ['trash', 'deleted'])),
                RestoreBulkAction::make()->visible(fn (): bool => in_array($currentTab, ['trash', 'deleted'])),
            ])
            ->filters([
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
            ]);
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
            'index' => Pages\ListCategories::route('/'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
            'create' => Pages\CreateCategory::route('/create'),
            'view' => Pages\ViewCategory::route('/{record}'),
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

    public static function getTableQuery(?string $currentTab = null): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes();

        if ($currentTab === 'trash' || $currentTab === 'deleted') {
            $model = static::getModel();
            if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query->whereNotNull($model::make()->getQualifiedDeletedAtColumn());
            }
        }

        return $query;
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }
}
