<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Resources;

use App\Forms\Components\JsonField;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;
use Moox\Core\Traits\Tabs\TabsInResource;
use Moox\DataLanguages\Resources\StaticCurrencyResource\Pages;

class StaticCurrencyResource extends Resource
{
    use BaseInResource, SingleSimpleInResource, TabsInResource;

    protected static ?string $model = \Moox\DataLanguages\Models\StaticCurrency::class;

    protected static ?string $navigationIcon = 'gmdi-euro';

    public static function getModelLabel(): string
    {
        return __('data-languages::static-currency.static_currency');
    }

    public static function getPluralModelLabel(): string
    {
        return __('data-languages::static-currency.static_currencies');
    }

    public static function getNavigationLabel(): string
    {
        return __('data-languages::static-currency.static_currencies');
    }

    public static function getBreadcrumb(): string
    {
        return __('data-languages::static-currency.static_currency');
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
                                    TextInput::make('code')
                                        ->label(__('data-languages::static-currency.code'))
                                        ->maxLength(3)
                                        ->required(),
                                    TextInput::make('common_name')
                                        ->label(__('data-languages::data-languages.common_name'))
                                        ->required(),
                                    TextInput::make('symbol')
                                        ->label(__('data-languages::static-currency.symbol'))
                                        ->maxLength(10)
                                        ->nullable(),
                                    JsonField::make('exonyms')
                                        ->label(__('data-languages::data-languages.exonyms'))
                                        ->required(),
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
                TextColumn::make('symbol')->label(__('data-languages::static-currency.symbol')),
                TextColumn::make('code')->label(__('data-languages::static-currency.code')),
                TextColumn::make('common_name')->label(__('data-languages::data-languages.common_name')),
                TextColumn::make('exonyms')->label(__('data-languages::data-languages.exonyms')),
            ])
            ->defaultSort('id', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                Filter::make('id')
                    ->form([
                        TextInput::make('id')
                            ->label('ID')
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
                    ->form([
                        TextInput::make('code')
                            ->label('Code')
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
                    ->form([
                        TextInput::make('common_name')
                            ->label('Common Name')
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
                    ->form([
                        TextInput::make('symbol')
                            ->label('Symbol')
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
                    ->form([
                        TextInput::make('exonyms')
                            ->label('Exonyms')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaticCurrencies::route('/'),
            'create' => Pages\CreateStaticCurrency::route('/create'),
            'edit' => Pages\EditStaticCurrency::route('/{record}/edit'),
            'view' => Pages\ViewStaticCurrency::route('/{record}'),
        ];
    }
}
