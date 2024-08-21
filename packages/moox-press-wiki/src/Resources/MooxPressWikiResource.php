<?php

namespace Moox\MooxPressWiki\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\MooxPressWiki\Models\WpWiki;
use Moox\MooxPressWiki\Resources\MooxPressWikiResource\Pages\ListPage;
use Moox\MooxPressWiki\Resources\MooxPressWikiResource\Widgets\MooxPressWikiWidgets;

class MooxPressWikiResource extends Resource
{
    protected static ?string $model = WpWiki::class;

    protected static ?string $navigationIcon = 'gmdi-engineering';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('core::core.name'))
                    ->maxLength(255),
                DateTimePicker::make('started_at')
                    ->label(__('core::core.started_at')),
                DateTimePicker::make('finished_at')
                    ->label(__('core::core.finished_at')),
                Toggle::make('failed')
                    ->label(__('core::core.failed'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('core::core.name'))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('core::core.started_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('failed')
                    ->label(__('core::core.failed'))
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
            MooxPressWikiWidgets::class,
        ];
    }

    public static function getModelLabel(): string
    {
        return config('moox-press-wiki.resources.moox-press-wiki.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('moox-press-wiki.resources.moox-press-wiki.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('moox-press-wiki.resources.moox-press-wiki.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('moox-press-wiki.resources.moox-press-wiki.single');
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
        return config('moox-press-wiki.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('moox-press-wiki.navigation_sort') + 3;
    }
}
