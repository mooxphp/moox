<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Entities\Items\Draft\BaseDraftResource;
use Moox\Data\Filament\Resources\StaticCurrencyResource\Pages\CreateStaticCurrency;
use Moox\Data\Filament\Resources\StaticCurrencyResource\Pages\EditStaticCurrency;
use Moox\Data\Filament\Resources\StaticCurrencyResource\Pages\ListStaticCurrencies;
use Moox\Data\Filament\Resources\StaticCurrencyResource\Pages\ViewStaticCurrency;
use Moox\Data\Filament\Resources\StaticCurrencyResource\RelationManagers\StaticCountryRelationManager;
use Moox\Data\Models\StaticCurrency;

class StaticCurrencyResource extends BaseDraftResource
{
    protected static ?string $model = StaticCurrency::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-euro';

    public static function getModelLabel(): string
    {
        return config('static-currency.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('static-currency.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('static-currency.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('static-currency.single');
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
                                TextInput::make('code')
                                    ->label(__('data::fields.code'))
                                    ->maxLength(3)
                                    ->required(),
                                TextInput::make('common_name')
                                    ->label(__('data::fields.common_name'))
                                    ->required(),
                                TextInput::make('symbol')
                                    ->label(__('data::fields.symbol'))
                                    ->maxLength(10)
                                    ->nullable(),
                                KeyValue::make('exonyms')
                                    ->label(__('data::fields.exonyms'))
                                    ->required(),
                            ])
                            ->columnSpan(2),
                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        static::getFormActions(),
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
                TextColumn::make('symbol')->label(__('data::fields.symbol')),
                TextColumn::make('code')->label(__('data::fields.code')),
                TextColumn::make('common_name')->label(__('data::fields.common_name')),
                TextColumn::make('exonyms')->label(__('data::fields.exonyms')),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                Filter::make('id')
                    ->schema([
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
                Filter::make('code')
                    ->schema([
                        TextInput::make('code')
                            ->label(__('data::fields.code'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['code'],
                            fn (Builder $query, $value): Builder => $query->where('code', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['code']) {
                            return null;
                        }

                        return 'Code: '.$data['code'];
                    }),
                Filter::make('common_name')
                    ->schema([
                        TextInput::make('common_name')
                            ->label(__('data::fields.common_name'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['common_name'],
                            fn (Builder $query, $value): Builder => $query->where('common_name', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['common_name']) {
                            return null;
                        }

                        return 'Common Name: '.$data['common_name'];
                    }),
                Filter::make('symbol')
                    ->schema([
                        TextInput::make('symbol')
                            ->label(__('data::fields.symbol'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['symbol'],
                            fn (Builder $query, $value): Builder => $query->where('symbol', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['symbol']) {
                            return null;
                        }

                        return 'Symbol: '.$data['symbol'];
                    }),
                Filter::make('exonyms')
                    ->schema([
                        TextInput::make('exonyms')
                            ->label(__('data::fields.exonyms'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['exonyms'],
                            fn (Builder $query, $value): Builder => $query->where('exonyms', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['exonyms']) {
                            return null;
                        }

                        return 'Exonyms: '.$data['exonyms'];
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [StaticCountryRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaticCurrencies::route('/'),
            'create' => CreateStaticCurrency::route('/create'),
            'edit' => EditStaticCurrency::route('/{record}/edit'),
            'view' => ViewStaticCurrency::route('/{record}'),
        ];
    }
}
