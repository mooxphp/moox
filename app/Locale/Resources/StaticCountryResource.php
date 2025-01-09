<?php

declare(strict_types=1);

namespace App\Locale\Resources;

use App\Locale\Resources\StaticCountryResource\Pages;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;
use App\Forms\Components\JsonField;

class StaticCountryResource extends Resource
{
    use BaseInResource, SingleSimpleInResource;

    protected static ?string $model = \App\Locale\Models\StaticCountry::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
        return config('static-country.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('static-country.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('navigation_sort') + 1;
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
                                        ->label(__('locale.alpha2'))
                                        ->maxLength(3)->required(),
                                    TextInput::make('alpha3_b')
                                        ->label(__('locale.alpha3_b'))
                                        ->maxLength(3)->nullable(),
                                    TextInput::make('alpha3_t')
                                        ->label(__('locale.alpha3_t'))
                                        ->maxLength(3)->nullable(),
                                    TextInput::make('common_name')
                                        ->label(__('locale.common_name'))
                                        ->maxLength(255)->required(),
                                    TextInput::make('native_name')
                                        ->label(__('locale.native_name'))
                                        ->maxLength(255)->nullable(),
                                    JsonField::make('exonyms')->label(__('locale.exonyms')),
                                    TextInput::make('calling_code')
                                        ->label(__('entities/static-country.calling_code'))
                                        ->numeric()->maxValue(100),
                                    TextInput::make('capital')
                                        ->label(__('entities/static-country.capital'))
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('population')
                                        ->label(__('entities/static-country.population'))
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('area')
                                        ->label(__('entities/static-country.area'))
                                        ->maxLength(255)->nullable(),
                                    JsonField::make('links')
                                        ->label(__('entities/static-country.links')),
                                    JsonField::make('tlds')
                                        ->rows(4)
                                        ->label(__('entities/static-country.tlds')),
                                    JsonField::make('membership')
                                        ->rows(7)
                                        ->label(__('entities/static-country.membership')),
                                    JsonField::make('embargo_data')
                                        ->rows(2)
                                        ->label(__('entities/static-country.embargo_data')),
                                    JsonField::make('address_format')
                                        ->rows(7)
                                        ->label(__('entities/static-country.address_format')),
                                    TextInput::make('postal_code_regex')
                                        ->label(__('entities/static-country.postal_code_regex'))
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('dialing_prefix')
                                        ->label(__('entities/static-country.dialing_prefix'))
                                        ->maxLength(10)->nullable(),
                                    JsonField::make('phone_number_formatting')
                                        ->rows(5)
                                        ->label(__('entities/static-country.phone_number_formatting')),
                                    TextInput::make('date_format')
                                        ->label(__('entities/static-country.date_format'))
                                        ->maxLength(10)->required(),
                                    JsonField::make('currency_format')
                                        ->rows(7)
                                        ->label(__('entities/static-country.currency_format')),
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
                                        ->label(__('entities/static-country.region'))
                                        ->options(__('entities/static-country.region_options')),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('subregion')
                                        ->label(__('entities/static-country.subregion'))
                                        ->options(__('entities/static-country.subregion_options')),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('embargo')
                                        ->label(__('entities/static-country.embargo'))
                                        ->options(__('entities/static-country.embargo_options')),
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
                TextColumn::make('alpha2')->label(__('locale.alpha2')),
                TextColumn::make('alpha3_b')->label(__('locale.alpha3_b')),
                TextColumn::make('alpha3_t')->label(__('locale.alpha3_t')),
                TextColumn::make('common_name')->label(__('locale.common_name')),
                TextColumn::make('native_name')->label(__('locale.native_name')),
                TextColumn::make('region')->sortable()->searchable()->toggleable()->label(__('entities/static-country.region')),
                TextColumn::make('subregion')->sortable()->searchable()->toggleable()->label(__('entities/static-country.subregion')),
                TextColumn::make('capital')->label(__('entities/static-country.capital')),
                TextColumn::make('population')->label(__('entities/static-country.population')),
                TextColumn::make('area')->label(__('entities/static-country.area')),
                TextColumn::make('embargo')->sortable()->searchable()->toggleable()->label(__('entities/static-country.embargo')),
                TextColumn::make('postal_code_regex')->label(__('entities/static-country.postal_code_regex')),
                TextColumn::make('dialing_prefix')->label(__('entities/static-country.dialing_prefix')),
                TextColumn::make('date_format')->label(__('entities/static-country.date_format')),
            ])
            ->defaultSort('id', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
            ->filters([]);
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
