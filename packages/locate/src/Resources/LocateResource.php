<?php

namespace Moox\Locate\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Locate\Models\Locate;
use Moox\Locate\Resources\LocateResource\Pages\ListPage;
use Moox\Locate\Resources\LocateResource\Widgets\LocateWidgets;

class LocateResource extends Resource
{
    protected static ?string $model = Locate::class;

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
                    ->label(__('locate::translations.name'))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('locate::translations.started_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('failed')
                    ->label(__('locate::translations.failed'))
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
            LocateWidgets::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return __('locate::translations.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('locate::translations.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('locate::translations.navigation_label');
    }

    public static function getBreadcrumb(): string
    {
        return __('locate::translations.breadcrumb');
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
        return __('locate::translations.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('locate.navigation_sort');
    }
}
