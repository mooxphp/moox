<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;
use Moox\Data\Filament\Resources\StaticCountriesStaticTimezonesResource\Pages;

class StaticCountriesStaticTimezonesResource extends Resource
{
    use BaseInResource, SingleSimpleInResource;

    protected static ?string $model = \Moox\Data\Models\StaticCountriesStaticTimezones::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return config('static-countries-static-timezones.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('static-countries-static-timezones.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('static-countries-static-timezones.single');
    }

    public static function getBreadcrumb(): string
    {
        return config('static-countries-static-timezones.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('data.navigation-group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('data.navigation-sort') + 1;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(2)
                ->schema([
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    Select::make('country_id')
                                        ->label(__('data::fields.country'))
                                        ->relationship('country', 'alpha3_t')
                                        ->searchable()
                                        ->preload()->required(),
                                    Select::make('timezone_id')
                                        ->label(__('data::fields.timezone'))
                                        ->relationship('timezone', 'name')
                                        ->searchable()
                                        ->preload()->required(),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                ->columns(['lg' => 3]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('country.alpha3_t')
                    ->label(__('data::fields.country_alpha3_t'))
                    ->sortable(),
                TextColumn::make('timezone.name')
                    ->label(__('data::fields.timezone_name'))
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                SelectFilter::make('country')
                    ->label(__('data::fields.country_name'))
                    ->relationship('country', 'alpha3_t'),
                SelectFilter::make('timezone')
                    ->label(__('data::fields.timezone_name'))
                    ->relationship('timezone', 'name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaticCountriesStaticTimezones::route('/'),
            'create' => Pages\CreateStaticCountriesStaticTimezones::route('/create'),
            'edit' => Pages\EditStaticCountriesStaticTimezones::route('/{record}/edit'),
            'view' => Pages\ViewStaticCountriesStaticTimezones::route('/{record}'),
        ];
    }
}
