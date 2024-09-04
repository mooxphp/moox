<?php

namespace Moox\Builder\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Builder\Models\Item;
use Moox\Builder\Resources\ItemResource\Pages\ListPage;
use Moox\Builder\Resources\ItemResource\Widgets\ItemWidgets;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'gmdi-engineering';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label(__('core::core.title'))
                    ->required(),
                TextInput::make('slug')
                    ->label(__('core::core.slug'))
                    ->required(),
                TextInput::make('content')
                    ->label(__('core::core.content'))
                    ->required(),
                Item::getStatusFormField(),
                Item::getTypeFormField(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('core::core.title'))
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('core::core.slug'))
                    ->sortable(),
                TextColumn::make('content')
                    ->label(__('core::core.content'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('core::core.status'))
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('core::core.type'))
                    ->sortable(),
            ])
            ->defaultSort('slug', 'desc')
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
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
            'index' => ListPage::route('/'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            ItemWidgets::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return config('builder.resources.builder.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('builder.resources.builder.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('builder.resources.builder.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('builder.resources.builder.single');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count());
    }

    public static function getNavigationGroup(): ?string
    {
        return config('builder.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('builder.navigation_sort') + 3;
    }
}
