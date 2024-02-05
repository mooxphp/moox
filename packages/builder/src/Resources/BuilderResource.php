<?php

namespace Moox\Builder\Resources;

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
use Moox\Builder\BuilderPlugin;
use Moox\Builder\Models\Builder;
use Moox\Builder\Resources\BuilderResource\Pages\ListPage;
use Moox\Builder\Resources\BuilderResource\Widgets\BuilderWidgets;

class BuilderResource extends Resource
{
    protected static ?string $model = Builder::class;

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
                    ->label(__('builder::translations.name'))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('builder::translations.started_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('failed')
                    ->label(__('builder::translations.failed'))
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
            BuilderWidgets::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return BuilderPlugin::make()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function getModelLabel(): string
    {
        return BuilderPlugin::make()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return BuilderPlugin::make()->getPluralLabel();
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        return BuilderPlugin::make()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return BuilderPlugin::make()->getNavigationSort();
    }

    public static function getBreadcrumb(): string
    {
        return BuilderPlugin::make()->getBreadcrumb();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return BuilderPlugin::make()->shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): string
    {
        return BuilderPlugin::make()->getNavigationIcon();
    }
}
