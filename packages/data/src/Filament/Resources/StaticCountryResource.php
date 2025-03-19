<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;
use Moox\Data\Filament\Resources\StaticCountryResource\Pages;
use Moox\Data\Filament\Resources\StaticCountryResource\RelationManagers;

class StaticCountryResource extends Resource
{
    use BaseInResource, SingleSimpleInResource;

    protected static ?string $model = \Moox\Data\Models\StaticCountry::class;

    protected static ?string $navigationIcon = 'gmdi-flag-o';

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
                        ->columnSpan(['lg' => 1]),
                ])
                ->columns(['lg' => 3]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('alpha2')
                    ->label('')
                    ->icon(fn (string $state): ?string => @file_exists(base_path("packages/flag-icons-circle/resources/svg/{$state}.svg")) ? "flag-{$state}" : null),
                TextColumn::make('alpha2_')
                    ->label('Alpha-2')
                    ->getStateUsing(fn ($record) => $record->alpha2),
                TextColumn::make('alpha3_b')->label(__('data::fields.alpha3_b')),
                TextColumn::make('alpha3_t')->label(__('data::fields.alpha3_t')),
                TextColumn::make('common_name')->label(__('data::fields.common_name')),
                TextColumn::make('native_name')->label(__('data::fields.native_name')),
                TextColumn::make('region')->sortable()->searchable()->toggleable()->label(__('data::fields.region')),
                TextColumn::make('subregion')->sortable()->searchable()->toggleable()->label(__('data::fields.subregion')),
                TextColumn::make('capital')->label(__('data::fields.capital')),
                TextColumn::make('population')->label(__('data::fields.population')),
                TextColumn::make('area')->label(__('data::fields.area')),
                TextColumn::make('embargo')->sortable()->searchable()->toggleable()->label(__('data::fields.embargo')),
                TextColumn::make('postal_code_regex')->label(__('data::fields.postal_code_regex')),
                TextColumn::make('dialing_prefix')->label(__('data::fields.dialing_prefix')),
                TextColumn::make('date_format')->label(__('data::fields.date_format')),
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
