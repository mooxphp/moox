<?php

namespace Moox\Skeleton\Resources;

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
use Moox\Skeleton\Models\Skeleton;
use Moox\Skeleton\Resources\SkeletonResource\Pages\ListPage;
use Moox\Skeleton\Resources\SkeletonResource\Widgets\SkeletonWidgets;
use Moox\Skeleton\SkeletonPlugin;

class SkeletonResource extends Resource
{
    protected static ?string $model = Skeleton::class;

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
                    ->label(__('skeleton::translations.name'))
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('skeleton::translations.started_at'))
                    ->since()
                    ->sortable(),
                TextColumn::make('failed')
                    ->label(__('skeleton::translations.failed'))
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
            SkeletonWidgets::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return SkeletonPlugin::get()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function getModelLabel(): string
    {
        return SkeletonPlugin::get()->getLabel();
    }

    public static function getPluralModelLabel(): string
    {
        return SkeletonPlugin::get()->getPluralLabel();
    }

    public static function getNavigationLabel(): string
    {
        return Str::title(static::getPluralModelLabel()) ?? Str::title(static::getModelLabel());
    }

    public static function getNavigationGroup(): ?string
    {
        return SkeletonPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return SkeletonPlugin::get()->getNavigationSort();
    }

    public static function getBreadcrumb(): string
    {
        return SkeletonPlugin::get()->getBreadcrumb();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return SkeletonPlugin::get()->shouldRegisterNavigation();
    }

    public static function getNavigationIcon(): string
    {
        return SkeletonPlugin::get()->getNavigationIcon();
    }
}
