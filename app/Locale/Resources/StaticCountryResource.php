<?php

declare(strict_types=1);

namespace App\Locale\Resources;

use App\Locale\Resources\StaticCountryResource\Pages;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\NumberFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;

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
                                        ->label('Alpha-2 Code')
                                        ->maxLength(3)->required(),
                                    TextInput::make('alpha3_b')
                                        ->label('Alpha-3 Code (B)')
                                        ->maxLength(3)->nullable(),
                                    TextInput::make('alpha3_t')
                                        ->label('Alpha-3 Code (T)')
                                        ->maxLength(3)->nullable(),
                                    TextInput::make('common_name')
                                        ->label('Common Name')
                                        ->maxLength(255)->required(),
                                    TextInput::make('native_name')
                                        ->label('Native Name')
                                        ->maxLength(255)->nullable(),
                                    KeyValue::make('exonyms')
                                        ->label('Exonyms'),
                                    TextInput::make('calling_code')
                                        ->label('Calling Code')
                                        ->numeric()->maxValue(100),
                                    TextInput::make('capital')
                                        ->label('Capital')
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('population')
                                        ->label('Population')
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('area')
                                        ->label('Area')
                                        ->maxLength(255)->nullable(),
                                    KeyValue::make('links')
                                        ->label('Links'),
                                    KeyValue::make('tlds')
                                        ->label('TLDs'),
                                    KeyValue::make('membership')
                                        ->label('Membership'),
                                    KeyValue::make('embargo_data')
                                        ->label('Embargo Data'),
                                    KeyValue::make('address_format')
                                        ->label('Address Format'),
                                    TextInput::make('postal_code_regex')
                                        ->label('Postal Code Regex')
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('dialing_prefix')
                                        ->label('Dialing Prefix')
                                        ->maxLength(10)->nullable(),
                                    KeyValue::make('phone_number_formatting')
                                        ->label('Phone Number Formatting'),
                                    TextInput::make('date_format')
                                        ->label('Date Format')
                                        ->maxLength(10)->required(),
                                    KeyValue::make('currency_format')
                                        ->label('Currency Format'),
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
                                        ->label('Region')
                                        ->placeholder(__('core::core.type'))
                                        ->options(['Africa' => 'Africa', 'Americas' => 'Americas', 'Asia' => 'Asia', 'Europe' => 'Europe', 'Oceania' => 'Oceania', 'Antarctica' => 'Antarctica']),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('subregion')
                                        ->label('Subregion')
                                        ->placeholder(__('core::core.type'))
                                        ->options(['Northern Africa' => 'Northern Africa', 'Sub-Saharan Africa' => 'Sub-Saharan Africa', 'Eastern Africa' => 'Eastern Africa', 'Middle Africa' => 'Middle Africa', 'Southern Africa' => 'Southern Africa', 'Western Africa' => 'Western Africa', 'Latin America and the Caribbean' => 'Latin America and the Caribbean', 'Northern America' => 'Northern America', 'Caribbean' => 'Caribbean', 'Central America' => 'Central America', 'South America' => 'South America', 'Central Asia' => 'Central Asia', 'Eastern Asia' => 'Eastern Asia', 'South-Eastern Asia' => 'South-Eastern Asia', 'Southern Asia' => 'Southern Asia', 'Western Asia' => 'Western Asia', 'Eastern Europe' => 'Eastern Europe', 'Northern Europe' => 'Northern Europe', 'Southern Europe' => 'Southern Europe', 'Western Europe' => 'Western Europe', 'Australia and New Zealand' => 'Australia and New Zealand', 'Melanesia' => 'Melanesia', 'Micronesia' => 'Micronesia', 'Polynesia' => 'Polynesia']),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('embargo')
                                        ->label('Embargo')
                                        ->placeholder(__('core::core.type'))
                                        ->options(['New' => 'New', 'Open' => 'Open', 'Pending' => 'Pending', 'Closed' => 'Closed']),
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
                TextColumn::make('alpha2'),
                TextColumn::make('alpha3_b'),
                TextColumn::make('alpha3_t'),
                TextColumn::make('common_name'),
                TextColumn::make('native_name'),
                TextColumn::make('region')->sortable()->searchable()->toggleable(),
                TextColumn::make('subregion')->sortable()->searchable()->toggleable(),
                TextColumn::make('capital'),
                TextColumn::make('population'),
                TextColumn::make('area'),
                TextColumn::make('embargo')->sortable()->searchable()->toggleable(),
                TextColumn::make('postal_code_regex'),
                TextColumn::make('dialing_prefix'),
                TextColumn::make('date_format'),
            ])
            ->defaultSort('id', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                Filter::make('alpha2')
                    ->form([
                        TextInput::make('alpha2')
                            ->label('Alpha-2 Code')
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['alpha2'],
                            fn(Builder $query, $value): Builder => $query->where('alpha2', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['alpha2']) {
                            return null;
                        }

                        return 'Alpha-2 Code: ' . $data['alpha2'];
                    }),
                Filter::make('alpha3_b')
                    ->form([
                        TextInput::make('alpha3_b')
                            ->label('Alpha-3 Code (B)')
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['alpha3_b'],
                            fn(Builder $query, $value): Builder => $query->where('alpha3_b', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['alpha3_b']) {
                            return null;
                        }

                        return 'Alpha-3 Code (B): ' . $data['alpha3_b'];
                    }),
                Filter::make('alpha3_t')
                    ->form([
                        TextInput::make('alpha3_t')
                            ->label('Alpha-3 Code (T)')
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['alpha3_t'],
                            fn(Builder $query, $value): Builder => $query->where('alpha3_t', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['alpha3_t']) {
                            return null;
                        }

                        return 'Alpha-3 Code (T): ' . $data['alpha3_t'];
                    }),
                Filter::make('common_name')
                    ->form([
                        TextInput::make('common_name')
                            ->label('Common Name')
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['common_name'],
                            fn(Builder $query, $value): Builder => $query->where('common_name', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['common_name']) {
                            return null;
                        }

                        return 'Common Name: ' . $data['common_name'];
                    }),
                Filter::make('native_name')
                    ->form([
                        TextInput::make('native_name')
                            ->label('Native Name')
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['native_name'],
                            fn(Builder $query, $value): Builder => $query->where('native_name', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['native_name']) {
                            return null;
                        }

                        return 'Native Name: ' . $data['native_name'];
                    }),
                Filter::make('has_exonyms')
                    ->query(fn($query) => $query->whereNotNull('exonyms')),
                SelectFilter::make('region')
                    ->label('Region')
                    ->placeholder(__('core::core.filter') . ' Region')
                    ->options(['Africa' => 'Africa', 'Americas' => 'Americas', 'Asia' => 'Asia', 'Europe' => 'Europe', 'Oceania' => 'Oceania', 'Antarctica' => 'Antarctica']),
                SelectFilter::make('subregion')
                    ->label('Subregion')
                    ->placeholder(__('core::core.filter') . ' Subregion')
                    ->options(['Northern Africa' => 'Northern Africa', 'Sub-Saharan Africa' => 'Sub-Saharan Africa', 'Eastern Africa' => 'Eastern Africa', 'Middle Africa' => 'Middle Africa', 'Southern Africa' => 'Southern Africa', 'Western Africa' => 'Western Africa', 'Latin America and the Caribbean' => 'Latin America and the Caribbean', 'Northern America' => 'Northern America', 'Caribbean' => 'Caribbean', 'Central America' => 'Central America', 'South America' => 'South America', 'Central Asia' => 'Central Asia', 'Eastern Asia' => 'Eastern Asia', 'South-Eastern Asia' => 'South-Eastern Asia', 'Southern Asia' => 'Southern Asia', 'Western Asia' => 'Western Asia', 'Eastern Europe' => 'Eastern Europe', 'Northern Europe' => 'Northern Europe', 'Southern Europe' => 'Southern Europe', 'Western Europe' => 'Western Europe', 'Australia and New Zealand' => 'Australia and New Zealand', 'Melanesia' => 'Melanesia', 'Micronesia' => 'Micronesia', 'Polynesia' => 'Polynesia']),
                Filter::make('capital')
                    ->form([
                        TextInput::make('capital')
                            ->label('Capital')
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['capital'],
                            fn(Builder $query, $value): Builder => $query->where('capital', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['capital']) {
                            return null;
                        }

                        return 'Capital: ' . $data['capital'];
                    }),
                Filter::make('population')
                    ->form([
                        TextInput::make('population')
                            ->label('Population')
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['population'],
                            fn(Builder $query, $value): Builder => $query->where('population', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['population']) {
                            return null;
                        }

                        return 'Population: ' . $data['population'];
                    }),
                Filter::make('area')
                    ->form([
                        TextInput::make('area')
                            ->label('Area')
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['area'],
                            fn(Builder $query, $value): Builder => $query->where('area', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['area']) {
                            return null;
                        }

                        return 'Area: ' . $data['area'];
                    }),
                Filter::make('has_links')
                    ->query(fn($query) => $query->whereNotNull('links')),
                Filter::make('has_tlds')
                    ->query(fn($query) => $query->whereNotNull('tlds')),
                Filter::make('has_membership')
                    ->query(fn($query) => $query->whereNotNull('membership')),
                SelectFilter::make('embargo')
                    ->label('Embargo')
                    ->placeholder(__('core::core.filter') . ' Embargo')
                    ->options(['New' => 'New', 'Open' => 'Open', 'Pending' => 'Pending', 'Closed' => 'Closed']),
                Filter::make('has_embargo_data')
                    ->query(fn($query) => $query->whereNotNull('embargo_data')),
                Filter::make('has_address_format')
                    ->query(fn($query) => $query->whereNotNull('address_format')),
                Filter::make('postal_code_regex')
                    ->form([
                        TextInput::make('postal_code_regex')
                            ->label('Postal Code Regex')
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['postal_code_regex'],
                            fn(Builder $query, $value): Builder => $query->where('postal_code_regex', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['postal_code_regex']) {
                            return null;
                        }

                        return 'Postal Code Regex: ' . $data['postal_code_regex'];
                    }),
                Filter::make('dialing_prefix')
                    ->form([
                        TextInput::make('dialing_prefix')
                            ->label('Dialing Prefix')
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['dialing_prefix'],
                            fn(Builder $query, $value): Builder => $query->where('dialing_prefix', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['dialing_prefix']) {
                            return null;
                        }

                        return 'Dialing Prefix: ' . $data['dialing_prefix'];
                    }),
                Filter::make('has_phone_number_formatting')
                    ->query(fn($query) => $query->whereNotNull('phone_number_formatting')),
                Filter::make('date_format')
                    ->form([
                        TextInput::make('date_format')
                            ->label('Date Format')
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['date_format'],
                            fn(Builder $query, $value): Builder => $query->where('date_format', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['date_format']) {
                            return null;
                        }

                        return 'Date Format: ' . $data['date_format'];
                    }),
                Filter::make('has_currency_format')
                    ->query(fn($query) => $query->whereNotNull('currency_format')),
            ]);
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
