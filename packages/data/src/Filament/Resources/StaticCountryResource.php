<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\Data\Filament\Resources\StaticCountryResource\Pages\CreateStaticCountry;
use Moox\Data\Filament\Resources\StaticCountryResource\Pages\EditStaticCountry;
use Moox\Data\Filament\Resources\StaticCountryResource\Pages\ListStaticCountries;
use Moox\Data\Filament\Resources\StaticCountryResource\Pages\ViewStaticCountry;
use Moox\Data\Filament\Resources\StaticCountryResource\RelationManagers\LocalesRelationManager;
use Moox\Data\Filament\Resources\StaticCountryResource\RelationManagers\StaticCurrencyRealtionManager;
use Moox\Data\Filament\Resources\StaticCountryResource\RelationManagers\StaticTimezoneRealtionManager;
use Moox\Data\Models\StaticCountry;

class StaticCountryResource extends BaseRecordResource
{
    protected static ?string $model = StaticCountry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-flag-o';

    public static function getModelLabel(): string
    {
        return config('static-country.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('static-country.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('static-country.single');
    }

    public static function getBreadcrumb(): string
    {
        return config('static-country.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('data.navigation-group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('alpha2')
                                    ->label(__('data::fields.alpha2'))
                                    ->maxLength(3)->required(),
                                TextInput::make('alpha3_b')
                                    ->label(__('data::fields.alpha3_b'))
                                    ->maxLength(3)->nullable(),
                                TextInput::make('alpha3_t')
                                    ->label(__('data::fields.alpha3_t'))
                                    ->maxLength(3)->nullable(),
                                TextInput::make('common_name')
                                    ->label(__('data::fields.common_name'))
                                    ->maxLength(255)->required(),
                                TextInput::make('native_name')
                                    ->label(__('data::fields.native_name'))
                                    ->maxLength(255)->nullable(),
                                Textarea::make('exonyms')
                                    ->label(__('data::fields.exonyms'))
                                    ->afterStateHydrated(function (Textarea $component, $state) {
                                        if (is_array($state) || is_object($state)) {
                                            $state = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                                        }

                                        $component->state($state);
                                    })
                                    ->rule('json'),
                                TextInput::make('calling_code')
                                    ->label(__('data::fields.calling_code'))
                                    ->numeric()->maxValue(100),
                                TextInput::make('capital')
                                    ->label(__('data::fields.capital'))
                                    ->maxLength(255)->nullable(),
                                TextInput::make('population')
                                    ->label(__('data::fields.population'))
                                    ->integer()
                                    ->nullable(),
                                TextInput::make('area')
                                    ->label(__('data::fields.area'))
                                    ->maxLength(255)->nullable(),
                                Textarea::make('links')
                                    ->label(__('data::fields.links')),
                                Textarea::make('tlds')
                                    ->rows(4)
                                    ->label(__('data::fields.tlds')),
                                Textarea::make('membership')
                                    ->rows(7)
                                    ->label(__('data::fields.membership')),
                                TextInput::make('embargo_data')
                                    ->label(__('data::fields.embargo_data')),
                                TextInput::make('address_format')
                                    ->label(__('data::fields.address_format')),
                                TextInput::make('postal_code_regex')
                                    ->label(__('data::fields.postal_code_regex'))
                                    ->maxLength(255)->nullable(),
                                TextInput::make('dialing_prefix')
                                    ->label(__('data::fields.dialing_prefix'))
                                    ->maxLength(10)->nullable(),
                                TextInput::make('phone_number_formatting')
                                    ->label(__('data::fields.phone_number_formatting')),
                                TextInput::make('date_format')
                                    ->label(__('data::fields.date_format'))
                                    ->maxLength(10)->required(),
                                TextInput::make('currency_format')
                                    ->label(__('data::fields.currency_format')),
                            ])
                            ->columnSpan(2),
                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        static::getFormActions(),
                                    ]),
                                Section::make('')
                                    ->schema([
                                        Select::make('region')
                                            ->label(__('data::fields.region'))
                                            ->options(__('data::enums/country-region')),
                                    ]),
                                Section::make('')
                                    ->schema([
                                        Select::make('subregion')
                                            ->label(__('data::fields.subregion'))
                                            ->options(__('data::enums/country-subregion')),
                                    ]),
                                Section::make('')
                                    ->schema([
                                        Select::make('embargo')
                                            ->label(__('data::fields.embargo'))
                                            ->options(__('data::enums/country-embargo')),
                                    ]),
                            ])
                            ->columns(1)
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('flag_icon')
                    ->label('')
                    ->icon(fn(string $state): string => $state),
                TextColumn::make('alpha2')
                    ->label('Alpha-2')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('alpha3_b')->label(__('data::fields.alpha3_b'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('alpha3_t')->label(__('data::fields.alpha3_t'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('common_name')->label(__('data::fields.common_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('native_name')->label(__('data::fields.native_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('region')
                    ->label(__('data::fields.region'))
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->badge(),
                TextColumn::make('subregion')
                    ->label(__('data::fields.subregion'))
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->badge(),
                TextColumn::make('capital')
                    ->label(__('data::fields.capital')),
                TextColumn::make('population')
                    ->label(__('data::fields.population'))
                    ->sortable()
                    ->toggleable()
                    ->numeric()
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.') . ' ' . __('data::fields.people')),
                TextColumn::make('area')
                    ->label(__('data::fields.area'))
                    ->sortable()
                    ->numeric()
                    ->formatStateUsing(fn($state) => $state ? number_format((float) $state, 2, ',', '.') . ' km²' : '-'),
                TextColumn::make('embargo')
                    ->label(__('data::fields.embargo'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('calling_code')
                    ->label(__('data::fields.calling_code'))
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn($state) => $state ? '+' . $state : '-')
                    ->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([]);
    }

    public static function getRelations(): array
    {
        return [
            LocalesRelationManager::class,
            StaticCurrencyRealtionManager::class,
            StaticTimezoneRealtionManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaticCountries::route('/'),
            'create' => CreateStaticCountry::route('/create'),
            'edit' => EditStaticCountry::route('/{record}/edit'),
            'view' => ViewStaticCountry::route('/{record}'),
        ];
    }
}
