<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;
use Moox\DataLanguages\Resources\StaticCountryResource\Pages;
use Moox\DataLanguages\Resources\StaticCountryResource\RelationManagers;

class StaticCountryResource extends Resource
{
    use BaseInResource, SingleSimpleInResource;

    protected static ?string $model = \Moox\DataLanguages\Models\StaticCountry::class;

    protected static ?string $navigationIcon = 'gmdi-flag-o';

    public static function getModelLabel(): string
    {
        return __('data-languages::static-country.static_country');
    }

    public static function getPluralModelLabel(): string
    {
        return __('data-languages::static-country.static_countries');
    }

    public static function getNavigationLabel(): string
    {
        return __('data-languages::static-country.static_countries');
    }

    public static function getBreadcrumb(): string
    {
        return __('data-languages::static-country.static_country');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('data-languages.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('data-languages.navigation_sort') + 1;
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
                                    TextInput::make('alpha2')
                                        ->label(__('data-languages::data-languages.alpha2'))
                                        ->maxLength(3)->required(),
                                    TextInput::make('alpha3_b')
                                        ->label(__('data-languages::data-languages.alpha3_b'))
                                        ->maxLength(3)->nullable(),
                                    TextInput::make('alpha3_t')
                                        ->label(__('data-languages::data-languages.alpha3_t'))
                                        ->maxLength(3)->nullable(),
                                    TextInput::make('common_name')
                                        ->label(__('data-languages::data-languages.common_name'))
                                        ->maxLength(255)->required(),
                                    TextInput::make('native_name')
                                        ->label(__('data-languages::data-languages.native_name'))
                                        ->maxLength(255)->nullable(),
                                    Textarea::make('exonyms')
                                        ->label(__('data-languages::data-languages.exonyms'))
                                        ->afterStateHydrated(function (Textarea $component, $state) {
                                            if (is_array($state) || is_object($state)) {
                                                $state = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                                            }

                                            $component->state($state);
                                        })
                                        ->rule('json'),
                                    TextInput::make('calling_code')
                                        ->label(__('data-languages::static-country.calling_code'))
                                        ->numeric()->maxValue(100),
                                    TextInput::make('capital')
                                        ->label(__('data-languages::static-country.capital'))
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('population')
                                        ->label(__('data-languages::static-country.population'))
                                        ->integer()
                                        ->nullable(),
                                    TextInput::make('area')
                                        ->label(__('data-languages::static-country.area'))
                                        ->maxLength(255)->nullable(),
                                    Textarea::make('links')
                                        ->label(__('data-languages::static-country.links')),
                                    Textarea::make('tlds')
                                        ->rows(4)
                                        ->label(__('data-languages::static-country.tlds')),
                                    Textarea::make('membership')
                                        ->rows(7)
                                        ->label(__('data-languages::static-country.membership')),
                                    TextInput::make('embargo_data')
                                        ->label(__('data-languages::static-country.embargo_data')),
                                    TextInput::make('address_format')
                                        ->label(__('data-languages::static-country.address_format')),
                                    TextInput::make('postal_code_regex')
                                        ->label(__('data-languages::static-country.postal_code_regex'))
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('dialing_prefix')
                                        ->label(__('data-languages::static-country.dialing_prefix'))
                                        ->maxLength(10)->nullable(),
                                    TextInput::make('phone_number_formatting')
                                        ->label(__('data-languages::static-country.phone_number_formatting')),
                                    TextInput::make('date_format')
                                        ->label(__('data-languages::static-country.date_format'))
                                        ->maxLength(10)->required(),
                                    TextInput::make('currency_format')
                                        ->label(__('data-languages::static-country.currency_format')),

                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('region')
                                        ->label(__('data-languages::static-country.region'))
                                        ->options(__('data-languages::static-country.region_options')),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('subregion')
                                        ->label(__('data-languages::static-country.subregion'))
                                        ->options(__('data-languages::static-country.subregion_options')),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('embargo')
                                        ->label(__('data-languages::static-country.embargo'))
                                        ->options(__('data-languages::static-country.embargo_options')),
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
                TextColumn::make('alpha2')->label(__('data-languages::data-languages.alpha2')),
                TextColumn::make('alpha3_b')->label(__('data-languages::data-languages.alpha3_b')),
                TextColumn::make('alpha3_t')->label(__('data-languages::data-languages.alpha3_t')),
                TextColumn::make('common_name')->label(__('data-languages::data-languages.common_name')),
                TextColumn::make('native_name')->label(__('data-languages::data-languages.native_name')),
                TextColumn::make('region')->sortable()->searchable()->toggleable()->label(__('data-languages::static-country.region')),
                TextColumn::make('subregion')->sortable()->searchable()->toggleable()->label(__('data-languages::static-country.subregion')),
                TextColumn::make('capital')->label(__('data-languages::static-country.capital')),
                TextColumn::make('population')->label(__('data-languages::static-country.population')),
                TextColumn::make('area')->label(__('data-languages::static-country.area')),
                TextColumn::make('embargo')->sortable()->searchable()->toggleable()->label(__('data-languages::static-country.embargo')),
                TextColumn::make('postal_code_regex')->label(__('data-languages::static-country.postal_code_regex')),
                TextColumn::make('dialing_prefix')->label(__('data-languages::static-country.dialing_prefix')),
                TextColumn::make('date_format')->label(__('data-languages::static-country.date_format')),
            ])
            ->defaultSort('id', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
            ->filters([]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LocalesRelationManager::class,
            RelationManagers\StaticCurrencyRealtionManager::class,
            RelationManagers\StaticTimezoneRealtionManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaticCountries::route('/'),
            'create' => Pages\CreateStaticCountry::route('/create'),
            'edit' => Pages\EditStaticCountry::route('/{record}/edit'),
            'view' => Pages\ViewStaticCountry::route('/{record}'),
        ];
    }
}
