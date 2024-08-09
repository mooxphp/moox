<?php

namespace Moox\Press\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Moox\Press\Models\WpCategory;
use Moox\Press\Models\WpTerm;
use Moox\Press\Resources\WpCategoryResource\Pages;

class WpCategoryResource extends Resource
{
    protected static ?string $model = WpCategory::class;

    protected static ?string $navigationIcon = 'gmdi-category';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 0])->schema([
                    TextInput::make('name')
                        ->label(__('core::common.name'))
                        ->rules(['max:200', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('slug')
                        ->label(__('core::common.slug'))
                        ->rules(['max:200', 'string'])
                        ->required()
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Textarea::make('description')
                        ->label(__('core::common.description'))
                        ->rules(['string'])
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Select::make('parent')
                        ->label(__('core::common.parent'))
                        ->options(fn () => WpTerm::pluck('name', 'term_id'))
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('term_group')
                        ->rules(['integer'])
                        ->label(__('core::common.term_group'))
                        ->required()
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    TextInput::make('count')
                        ->rules(['integer'])
                        ->label(__('core::common.count'))
                        ->required()
                        ->readonly()
                        ->default('0')
                        ->columnSpan([
                            'default' => 12,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('core::common.name'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('core::common.slug'))
                    ->toggleable()
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('core::common.description'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('parent')
                    ->label(__('core::common.parent'))
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('count')
                    ->label(__('core::common.count'))
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('term_group')
                    ->label(__('core::common.term_group'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->limit(50),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWpCategories::route('/'),
            'create' => Pages\CreateWpCategory::route('/create'),
            'view' => Pages\ViewWpCategory::route('/{record}'),
            'edit' => Pages\EditWpCategory::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('core::common.category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('core::common.categories');
    }

    public static function getNavigationLabel(): string
    {
        return __('core::common.categories');
    }

    public static function getBreadcrumb(): string
    {
        return __('core::common.category');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('press.press_navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('press.press_navigation_sort') + 1;
    }
}
