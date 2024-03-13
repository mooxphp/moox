<?php

namespace Moox\Page\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Page\Models\Page;
use Moox\Page\Resources\PageResource\Pages\ListPage;
use Moox\Page\Resources\PageResource\Widgets\PageWidgets;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->maxLength(255),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('finished_at'),
                Toggle::make('failed')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('page::translations.name'))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('page::translations.started_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('failed')
                    ->label(__('page::translations.failed'))
                    ->sortable(),
            ])
            ->defaultSort('name', 'desc')
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
            PageWidgets::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return __('page::translations.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('page::translations.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('page::translations.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('page::translations.navigation_group');
    }

    public static function getBreadcrumb(): string
    {
        return __('page::translations.breadcrumb');
    }

    public static function getNavigationSort(): ?int
    {
        return config('page.navigation_sort');
    }
}
