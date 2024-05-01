<?php

namespace Moox\Passkey\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Passkey\Models\Passkey;
use Moox\Passkey\Resources\PasskeyResource\Pages\ListPage;
use Moox\Passkey\Resources\PasskeyResource\Widgets\PasskeyWidgets;

class PasskeyResource extends Resource
{
    protected static ?string $model = Passkey::class;

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
                    ->label(__('passkey::translations.name'))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('passkey::translations.started_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('failed')
                    ->label(__('passkey::translations.failed'))
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
            PasskeyWidgets::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return __('passkey::translations.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('passkey::translations.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('passkey::translations.navigation_label');
    }

    public static function getBreadcrumb(): string
    {
        return __('passkey::translations.breadcrumb');
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
        return __('passkey::translations.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('passkey.navigation_sort');
    }
}
