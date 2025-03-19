<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Data\Filament\Resources\StaticCountriesStaticCurrenciesResource\Pages;

class StaticCountriesStaticCurrenciesResource extends Resource
{
    use BaseInResource, HasResourceTabs, SingleSimpleInResource;

    protected static ?string $model = \Moox\Data\Models\StaticCountriesStaticCurrencies::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return config('static-countries-static-currencies.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('static-countries-static-currencies.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('static-countries-static-currencies.single');
    }

    public static function getBreadcrumb(): string
    {
        return config('static-countries-static-currencies.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('data.navigation-group');
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
                                    Toggle::make('is_primary')
                                        ->label(__('data::fields.is_primary'))->required(),
                                    Select::make('country_id')
                                        ->label(__('data::fields.country_alpha3_t'))
                                        ->relationship('country', 'alpha3_b')
                                        ->searchable()
                                        ->preload()->required(),
                                    Select::make('currency_id')
                                        ->label(__('data::fields.currency_symbol'))
                                        ->relationship('currency', 'symbol')
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
                IconColumn::make('is_primary')
                    ->label(__('data::fields.is_primary'))
                    ->boolean(),
                TextColumn::make('currency.symbol')
                    ->label(__('data::fields.currency_symbol'))
                    ->sortable(),
                TextColumn::make('country.alpha3_b')
                    ->label(__('data::fields.country_alpha3_t'))
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                Filter::make('id')
                    ->form([
                        TextInput::make('id')
                            ->label(__('data::fields.id'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['id'],
                            fn (Builder $query, $value): Builder => $query->where('id', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['id']) {
                            return null;
                        }

                        return 'ID: '.$data['id'];
                    }),
                Filter::make('country_id')
                    ->form([
                        TextInput::make('country_id')
                            ->label(__('data::fields.country_id'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['country_id'],
                            fn (Builder $query, $value): Builder => $query->where('country_id', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['country_id']) {
                            return null;
                        }

                        return 'Country ID: '.$data['country_id'];
                    }),
                Filter::make('currency_id')
                    ->form([
                        TextInput::make('currency_id')
                            ->label(__('data::fields.currency_id'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['currency_id'],
                            fn (Builder $query, $value): Builder => $query->where('currency_id', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['currency_id']) {
                            return null;
                        }

                        return 'Currency ID: '.$data['currency_id'];
                    }),
                SelectFilter::make('currency')
                    ->label(__('data::fields.currency_name'))
                    ->relationship('currency', 'symbol'),
                SelectFilter::make('country')
                    ->label(__('data::fields.country_name'))
                    ->relationship('country', 'alpha3_t'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaticCountriesStaticCurrencies::route('/'),
            'create' => Pages\CreateStaticCountriesStaticCurrencies::route('/create'),
            'edit' => Pages\EditStaticCountriesStaticCurrencies::route('/{record}/edit'),
            'view' => Pages\ViewStaticCountriesStaticCurrencies::route('/{record}'),
        ];
    }
}
