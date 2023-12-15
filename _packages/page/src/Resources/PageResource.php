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
use Illuminate\Support\Str;
use Moox\Page\Models\Page;
use Moox\Page\PagePlugin;
use Moox\Page\Resources\PageResource\Pages\ListPage;
use Moox\Page\Resources\PageResource\Widgets\PageWidgets;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

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

    public static function getNavigationBadge(): ?string
    {
        return PagePlugin::make()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function getModelLabel(): string
    {
        return PagePlugin::make()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return PagePlugin::make()->getPluralLabel();
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        return PagePlugin::make()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return PagePlugin::make()->getNavigationSort();
    }

    public static function getBreadcrumb(): string
    {
        return PagePlugin::make()->getBreadcrumb();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return PagePlugin::make()->shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): string
    {
        return PagePlugin::make()->getNavigationIcon();
    }
}
