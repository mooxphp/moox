<?php

namespace Moox\Press\Resources;

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
use Moox\Press\PressPlugin;
use Moox\Press\Models\Press;
use Moox\Press\Resources\PressResource\Pages\ListPage;
use Moox\Press\Resources\PressResource\Widgets\PressWidgets;

class PressResource extends Resource
{
    protected static ?string $model = Press::class;

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
                    ->label(__('press::translations.name'))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('press::translations.started_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('failed')
                    ->label(__('press::translations.failed'))
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
            PressWidgets::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return PressPlugin::make()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function getModelLabel(): string
    {
        return PressPlugin::make()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return PressPlugin::make()->getPluralLabel();
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        return PressPlugin::make()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return PressPlugin::make()->getNavigationSort();
    }

    public static function getBreadcrumb(): string
    {
        return PressPlugin::make()->getBreadcrumb();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return PressPlugin::make()->shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): string
    {
        return PressPlugin::make()->getNavigationIcon();
    }
}
