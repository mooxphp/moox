<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\Data\Filament\Resources\StaticCountriesStaticTimezonesResource\Pages\CreateStaticCountriesStaticTimezones;
use Moox\Data\Filament\Resources\StaticCountriesStaticTimezonesResource\Pages\EditStaticCountriesStaticTimezones;
use Moox\Data\Filament\Resources\StaticCountriesStaticTimezonesResource\Pages\ListStaticCountriesStaticTimezones;
use Moox\Data\Filament\Resources\StaticCountriesStaticTimezonesResource\Pages\ViewStaticCountriesStaticTimezones;
use Moox\Data\Models\StaticCountriesStaticTimezones;

class StaticCountriesStaticTimezonesResource extends BaseRecordResource
{
    protected static ?string $model = StaticCountriesStaticTimezones::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
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
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
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
            'index' => ListStaticCountriesStaticTimezones::route('/'),
            'create' => CreateStaticCountriesStaticTimezones::route('/create'),
            'edit' => EditStaticCountriesStaticTimezones::route('/{record}/edit'),
            'view' => ViewStaticCountriesStaticTimezones::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
