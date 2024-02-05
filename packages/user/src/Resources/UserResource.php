<?php

namespace Moox\User\Resources;

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
use Moox\User\Models\User;
use Moox\User\Resources\UserResource\Pages\ListPage;
use Moox\User\Resources\UserResource\Widgets\UserWidgets;
use Moox\User\UserPlugin;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

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
                    ->label(__('user::translations.name'))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('user::translations.started_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('failed')
                    ->label(__('user::translations.failed'))
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
            UserWidgets::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return UserPlugin::make()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function getModelLabel(): string
    {
        return UserPlugin::make()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return UserPlugin::make()->getPluralLabel();
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        return UserPlugin::make()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return UserPlugin::make()->getNavigationSort();
    }

    public static function getBreadcrumb(): string
    {
        return UserPlugin::make()->getBreadcrumb();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return UserPlugin::make()->shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): string
    {
        return UserPlugin::make()->getNavigationIcon();
    }
}
