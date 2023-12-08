<?php

namespace Moox\Blog\Resources;

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
use Moox\Blog\BlogPlugin;
use Moox\Blog\Models\Blog;
use Moox\Blog\Resources\BlogResource\Pages\ListPage;
use Moox\Blog\Resources\BlogResource\Widgets\BlogWidgets;

class BlogResource extends Resource
{
    protected static ?string $model = Blog::class;

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
                    ->label(__('blog::translations.name'))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('blog::translations.started_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('failed')
                    ->label(__('blog::translations.failed'))
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
            BlogWidgets::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return BlogPlugin::get()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function getModelLabel(): string
    {
        return BlogPlugin::get()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return BlogPlugin::get()->getPluralLabel();
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel()) ?? Str::title(static::getModelLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        return BlogPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return BlogPlugin::get()->getNavigationSort();
    }

    public static function getBreadcrumb(): string
    {
        return BlogPlugin::get()->getBreadcrumb();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return BlogPlugin::get()->shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): string
    {
        return BlogPlugin::get()->getNavigationIcon();
    }
}
