<?php

declare(strict_types=1);

namespace Moox\Category\Resources;

use Camya\Filament\Forms\Components\TitleWithSlugInput;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Category\Models\Category;
use Moox\Category\Resources\CategoryResource\Pages\CreateCategory;
use Moox\Category\Resources\CategoryResource\Pages\EditCategory;
use Moox\Category\Resources\CategoryResource\Pages\ListCategories;
use Moox\Category\Resources\CategoryResource\Pages\ViewCategory;
use Moox\Core\Traits\Tabs\TabsInResource;

//use Moox\Core\Forms\Components\TitleWithSlugInput;

class CategoryResource extends Resource
{
    use TabsInResource;

    protected static ?string $model = Category::class;

    protected static ?string $currentTab = null;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->orderBy('_lft');
    }

    protected static ?string $navigationIcon = 'gmdi-category';

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
                                            ->disabledOptions(fn ($get) => [$get('id')])
                                            ->enableBranchNode(),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 2]),
                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Actions::make([
                                            Actions\Action::make('restore')
                                                ->label(__('core::core.restore'))
                                                ->color('success')
                                                ->button()
                                                ->extraAttributes(['class' => 'w-full'])
                                                ->action(fn ($record) => $record->restore())
                                                ->visible(fn ($livewire, $record) => $record && $record->trashed() && $livewire instanceof ViewCategory),
                                            Actions\Action::make('save')
                                                ->label(__('core::core.save'))
                                                ->color('primary')
                                                ->button()
                                                ->extraAttributes(['class' => 'w-full'])
                                                ->action(function ($livewire) {
                                                    $livewire instanceof CreateCategory ? $livewire->create() : $livewire->save();
                                                })
                                                ->visible(fn ($livewire) => $livewire instanceof CreateCategory || $livewire instanceof EditCategory),
                                            Actions\Action::make('saveAndCreateAnother')
                                                ->label(__('core::core.save_and_create_another'))
                                                ->color('secondary')
                                                ->button()
                                                ->extraAttributes(['class' => 'w-full'])
                                                ->action(function ($livewire) {
                                                    $livewire->saveAndCreateAnother();
                                                })
                                                ->visible(fn ($livewire) => $livewire instanceof CreateCategory),
                                            Actions\Action::make('cancel')
                                                ->label(__('core::core.cancel'))
                                                ->color('secondary')
                                                ->outlined()
                                                ->extraAttributes(['class' => 'w-full'])
                                                ->url(fn () => static::getUrl('index'))
                                                ->visible(fn ($livewire) => $livewire instanceof CreateCategory),
                                            Actions\Action::make('edit')
                                                ->label(__('core::core.edit'))
                                                ->color('primary')
                                                ->button()
                                                ->extraAttributes(['class' => 'w-full'])
                                                ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                                                ->visible(fn ($livewire, $record) => $livewire instanceof ViewCategory && ! $record->trashed()),
                                            Actions\Action::make('restore')
                                                ->label(__('core::core.restore'))
                                                ->color('success')
                                                ->button()
                                                ->extraAttributes(['class' => 'w-full'])
                                                ->action(fn ($record) => $record->restore())
                                                ->visible(fn ($livewire, $record) => $record && $record->trashed() && $livewire instanceof EditCategory),
                                            Actions\Action::make('delete')
                                                ->label(__('core::core.delete'))
                                                ->color('danger')
                                                ->link()
                                                ->extraAttributes(['class' => 'w-full'])
                                                ->action(fn ($record) => $record->delete())
                                                ->visible(fn ($livewire, $record) => $record && ! $record->trashed() && $livewire instanceof EditCategory),
                                        ]),
                                        ColorPicker::make('color'),
                                        TextInput::make('weight'),
                                        TextInput::make('count')
                                            ->disabled()
                                            ->visible(fn ($livewire, $record) => ($record && $livewire instanceof EditCategory) || ($record && $livewire instanceof ViewCategory)),
                                        DateTimePicker::make('created_at')
                                            ->disabled()
                                            ->visible(fn ($livewire, $record) => ($record && $livewire instanceof EditCategory) || ($record && $livewire instanceof ViewCategory)),
                                        DateTimePicker::make('updated_at')
                                            ->disabled()
                                            ->visible(fn ($livewire, $record) => ($record && $livewire instanceof EditCategory) || ($record && $livewire instanceof ViewCategory)),
                                        DateTimePicker::make('deleted_at')
                                            ->disabled()
                                            ->visible(fn ($livewire, $record) => $record && $record->trashed() && $livewire instanceof ViewCategory),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 1]),
                    ])
                    ->columns(['lg' => 3]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $currentTab = static::getCurrentTab();

        return $table
            ->query(fn () => static::getEloquentQuery())
            ->defaultSort('_lft', 'asc')
            ->columns([
                ImageColumn::make('featured_image_url')
                    ->label(__('core::core.image'))
                    ->defaultImageUrl(url('/moox/core/assets/noimage.svg'))
                    ->alignment('center')
                    ->square()
                    ->toggleable(),
                TextColumn::make('modified_title')
                    ->label('Title')
                    ->getStateUsing(function (Category $record): string {
                        $depth = $record->ancestors->count();
                        $prefix = str_repeat('--', $depth);

                        return "{$prefix} {$record->title}";
                    })
                    ->searchable(),
                TextColumn::make('slug')
                    ->label(__('core::core.slug'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('level')
                    ->label('Level')
                    ->getStateUsing(fn (Category $record): int => $record->ancestors->count() + 1)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('children_count')
                    ->label('Subs')
                    ->counts('children')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('parent.title')
                    ->label('Parent')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('content')
                    ->label(__('core::core.content'))
                    ->sortable()
                    ->limit(30)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('count')
                    ->label(__('core::core.count'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('weight')
                    ->label(__('tag::translations.weight'))
                    ->sortable()
                    ->toggleable(),
                ColorColumn::make('color')
                    ->label(__('tag::translations.color'))
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('slug', 'desc')
            ->actions([
                ViewAction::make(),
                EditAction::make()->hidden(fn () => in_array(static::getCurrentTab(), ['trash', 'deleted'])),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->hidden(function () use ($currentTab) {
                    $isHidden = in_array($currentTab, ['trash', 'deleted']);

                    return $isHidden;
                }),
                RestoreBulkAction::make()->visible(function () use ($currentTab) {
                    $isVisible = in_array($currentTab, ['trash', 'deleted']);

                    return $isVisible;
                }),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Parent Category')
                    ->relationship('parent', 'title', fn ($query) => $query->has('children'))
                    ->searchable(),
                SelectFilter::make('children_count')
                    ->label('Subs')
                    ->options([
                        '0' => '0',
                        '1-5' => '1-5',
                        '6-10' => '6-10',
                        '10+' => '10+',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'], function ($query, $option) {
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
                        });
                    }),
                SelectFilter::make('depth')
                    ->label('Level')
                    ->options(fn () => array_combine(range(1, 5), range(1, 5)))
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'], function ($query, $depth) {
                            $query->whereIn('id', function ($subquery) use ($depth) {
                                $subquery->select('id')
                                    ->from('categories as c')
                                    ->whereRaw('(SELECT COUNT(*) FROM categories as ancestors WHERE ancestors._lft < c._lft AND ancestors._rgt > c._rgt) = ?', [$depth - 1]);
                            });
                        });
                    }),
            ])
            ->defaultSort('slug', 'desc')
            ->actions([
                ViewAction::make(),
                EditAction::make()->hidden(fn () => in_array(static::getCurrentTab(), ['trash', 'deleted'])),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->hidden(function () use ($currentTab) {
                    $isHidden = in_array($currentTab, ['trash', 'deleted']);

                    return $isHidden;
                }),
                RestoreBulkAction::make()->visible(function () use ($currentTab) {
                    $isVisible = in_array($currentTab, ['trash', 'deleted']);

                    return $isVisible;
                }),
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Parent Category')
                    ->relationship('parent', 'title', fn ($query) => $query->has('children'))
                    ->searchable(),
                SelectFilter::make('children_count')
                    ->label('Subs')
                    ->options([
                        '0' => '0',
                        '1-5' => '1-5',
                        '6-10' => '6-10',
                        '10+' => '10+',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'], function ($query, $option) {
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
                        });
                    }),
                SelectFilter::make('depth')
                    ->label('Level')
                    ->options(fn () => array_combine(range(1, 5), range(1, 5)))
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['value'], function ($query, $depth) {
                            $query->whereIn('id', function ($subquery) use ($depth) {
                                $subquery->select('id')
                                    ->from('categories as c')
                                    ->whereRaw('(SELECT COUNT(*) FROM categories as ancestors WHERE ancestors._lft < c._lft AND ancestors._rgt > c._rgt) = ?', [$depth - 1]);
                            });
                        });
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'edit' => EditCategory::route('/{record}/edit'),
            'create' => CreateCategory::route('/create'),
            'view' => ViewCategory::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
        ];
    }

    public static function getModelLabel(): string
    {
        return config('category.resources.category.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('category.resources.category.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('category.resources.category.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('category.resources.category.single');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return config('category.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('category.navigation_sort') + 3;
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
