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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                                    ->maxLength(3)
                                    ->required(),
                                TextInput::make('alpha3_b')
                                    ->label(__('data::fields.alpha3_b'))
                                    ->maxLength(3)
                                    ->nullable(),
                                TextInput::make('alpha3_t')
                                    ->label(__('data::fields.alpha3_t'))
                                    ->maxLength(3)
                                    ->nullable(),
                                TextInput::make('common_name')
                                    ->label(__('data::fields.common_name'))
                                    ->maxLength(255)
                                    ->required(),
                                Textarea::make('native_name')
                                    ->label(__('data::fields.native_name'))
                                    ->afterStateHydrated(function (Textarea $component, $state) {
                                        if (is_array($state)) {
                                            $formatted = [];
                                            foreach ($state as $key => $value) {
                                                if (is_array($value) && isset($value['common']) && isset($value['official'])) {
                                                    $formatted[] = $key.': '.$value['common'].' | '.$value['official'];
                                                } elseif (is_array($value) && isset($value['common'])) {
                                                    $formatted[] = $key.': '.$value['common'];
                                                } elseif (is_string($value)) {
                                                    $formatted[] = $key.': '.$value;
                                                }
                                            }
                                            $component->state(implode("\n", $formatted));
                                        } elseif (is_string($state) && ! empty($state)) {
                                            $component->state($state);
                                        } else {
                                            $component->state('');
                                        }
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        if (empty($state)) {
                                            return [];
                                        }
                                        $result = [];
                                        $lines = array_filter(array_map('trim', explode("\n", $state)));
                                        foreach ($lines as $line) {
                                            if (strpos($line, ':') !== false) {
                                                [$key, $rest] = explode(':', $line, 2);
                                                $key = trim($key);
                                                $rest = trim($rest);

                                                if (! empty($key) && ! empty($rest)) {
                                                    // Check if it has both common and official (separated by |)
                                                    if (strpos($rest, '|') !== false) {
                                                        [$common, $official] = explode('|', $rest, 2);
                                                        $result[$key] = [
                                                            'common' => trim($common),
                                                            'official' => trim($official),
                                                        ];
                                                    } else {
                                                        // Only common name provided
                                                        $result[$key] = [
                                                            'common' => $rest,
                                                            'official' => $rest,
                                                        ];
                                                    }
                                                }
                                            }
                                        }

                                        return $result;
                                    })
                                    ->placeholder('eng: Jamaica | Jamaica'."\n".'jam: Jamaica | Jamaica')
                                    ->rows(3)
                                    ->nullable(),
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
                    ->icon(fn (string $state): string => $state),
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
                TextColumn::make('native_name')
                    ->label(__('data::fields.native_name'))
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            $values = array_filter($state, function ($value) {
                                return is_string($value) && ! empty($value);
                            });

                            return ! empty($values) ? implode(', ', $values) : '-';
                        }
                        if (is_string($state) && ! empty($state)) {
                            return $state;
                        }

                        return '-';
                    })
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
                    ->label(__('data::fields.capital'))
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            $values = array_filter($state, function ($value) {
                                return is_string($value) && ! empty($value);
                            });

                            return ! empty($values) ? implode(', ', $values) : '-';
                        }
                        if (is_string($state) && ! empty($state)) {
                            return $state;
                        }

                        return '-';
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('population')
                    ->label(__('data::fields.population'))
                    ->sortable()
                    ->toggleable()
                    ->numeric()
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.').' '.__('data::fields.people')),
                TextColumn::make('area')
                    ->label(__('data::fields.area'))
                    ->sortable()
                    ->numeric()
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, ',', '.').' km²' : '-'),
                TextColumn::make('embargo')
                    ->label(__('data::fields.embargo'))
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'none' => 'info',
                        'partial' => 'warning',
                        'full' => 'danger',
                    }),
                TextColumn::make('calling_code')
                    ->label(__('data::fields.calling_code'))
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state ? '+'.$state : '-')
                    ->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                SelectFilter::make('region')
                    ->label(__('data::fields.region'))
                    ->options(__('data::enums/country-region'))
                    ->multiple()
                    ->searchable(),
                SelectFilter::make('subregion')
                    ->label(__('data::fields.subregion'))
                    ->options(__('data::enums/country-subregion'))
                    ->multiple()
                    ->searchable(),
                SelectFilter::make('embargo')
                    ->label(__('data::fields.embargo'))
                    ->options(__('data::enums/country-embargo'))
                    ->multiple(),
                TernaryFilter::make('has_population')
                    ->label('Hat Einwohnerzahl')
                    ->placeholder('Alle Länder')
                    ->trueLabel('Mit Einwohnerzahl')
                    ->falseLabel('Ohne Einwohnerzahl')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('population')->where('population', '>', 0),
                        false: fn ($query) => $query->where(function ($q) {
                            $q->whereNull('population')->orWhere('population', 0);
                        }),
                    ),
                TernaryFilter::make('has_calling_code')
                    ->label('Hat Vorwahl')
                    ->placeholder('Alle Länder')
                    ->trueLabel('Mit Vorwahl')
                    ->falseLabel('Ohne Vorwahl')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('calling_code'),
                        false: fn ($query) => $query->whereNull('calling_code'),
                    ),
                SelectFilter::make('population_size')
                    ->label('Bevölkerungsgröße')
                    ->options([
                        'small' => 'Klein (< 1M)',
                        'medium' => 'Mittel (1M - 50M)',
                        'large' => 'Groß (50M - 200M)',
                        'huge' => 'Sehr groß (> 200M)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value']) {
                            'small' => $query->where('population', '<', 1000000),
                            'medium' => $query->whereBetween('population', [1000000, 50000000]),
                            'large' => $query->whereBetween('population', [50000000, 200000000]),
                            'huge' => $query->where('population', '>', 200000000),
                            default => $query,
                        };
                    }),
            ])->deferFilters(false);
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

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
