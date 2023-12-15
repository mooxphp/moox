<?php

namespace Moox\Data\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Moox\Data\DataPlugin;
use Moox\Data\Models\Data;
use Moox\Data\Resources\DataResource\Pages\ListPage;
use Moox\Data\Resources\DataResource\Widgets\DataWidgets;

class DataResource extends Resource
{
    protected static ?string $model = Data::class;

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
                    ->label(__('data::translations.name'))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('data::translations.started_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('failed')
                    ->label(__('data::translations.failed'))
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
            DataWidgets::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return DataPlugin::get()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function getModelLabel(): string
    {
        return DataPlugin::get()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return DataPlugin::get()->getPluralLabel();
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        return DataPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return DataPlugin::get()->getNavigationSort();
    }

    public static function getBreadcrumb(): string
    {
        return DataPlugin::get()->getBreadcrumb();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return DataPlugin::get()->shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): string
    {
        return DataPlugin::get()->getNavigationIcon();
    }
}
