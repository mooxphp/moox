<?php

namespace Moox\Logs\Resources;

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
use Moox\Logs\LogsPlugin;
use Moox\Logs\Models\Logs;
use Moox\Logs\Resources\LogsResource\Pages\ListPage;
use Moox\Logs\Resources\LogsResource\Widgets\LogsWidgets;

class LogsResource extends Resource
{
    protected static ?string $model = Logs::class;

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
                    ->label(__('logs::translations.name'))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('logs::translations.started_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('failed')
                    ->label(__('logs::translations.failed'))
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
            LogsWidgets::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return LogsPlugin::get()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function getModelLabel(): string
    {
        return LogsPlugin::get()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return LogsPlugin::get()->getPluralLabel();
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        return LogsPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return LogsPlugin::get()->getNavigationSort();
    }

    public static function getBreadcrumb(): string
    {
        return LogsPlugin::get()->getBreadcrumb();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return LogsPlugin::get()->shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): string
    {
        return LogsPlugin::get()->getNavigationIcon();
    }
}
