<?php

namespace Moox\Core\Resources;

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
use Moox\Core\CorePlugin;
use Moox\Core\Models\Core;
use Moox\Core\Resources\CoreResource\Pages\ListPage;
use Moox\Core\Resources\CoreResource\Widgets\CoreWidgets;

class CoreResource extends Resource
{
    protected static ?string $model = Core::class;

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
                    ->label(__('core::translations.name'))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('core::translations.started_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('failed')
                    ->label(__('core::translations.failed'))
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
            CoreWidgets::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return CorePlugin::get()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function getModelLabel(): string
    {
        return CorePlugin::get()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return CorePlugin::get()->getPluralLabel();
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel()) ?? Str::title(static::getModelLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        return CorePlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return CorePlugin::get()->getNavigationSort();
    }

    public static function getBreadcrumb(): string
    {
        return CorePlugin::get()->getBreadcrumb();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CorePlugin::get()->shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): string
    {
        return CorePlugin::get()->getNavigationIcon();
    }
}
