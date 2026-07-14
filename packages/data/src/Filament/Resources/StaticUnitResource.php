<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\Data\Filament\Resources\StaticUnitResource\Pages\CreateStaticUnit;
use Moox\Data\Filament\Resources\StaticUnitResource\Pages\EditStaticUnit;
use Moox\Data\Filament\Resources\StaticUnitResource\Pages\ListStaticUnits;
use Moox\Data\Filament\Resources\StaticUnitResource\Pages\ViewStaticUnit;
use Moox\Data\Models\StaticUnit;

class StaticUnitResource extends BaseRecordResource
{
    protected static ?string $model = StaticUnit::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-straighten';

    public static function getModelLabel(): string
    {
        return config('static-unit.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('static-unit.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('static-unit.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('static-unit.single');
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
                                    ->maxLength(10)
                                    ->required(),
                                TextInput::make('common_name')
                                    ->label(__('data::fields.common_name'))
                                    ->required(),
                                TextInput::make('symbol')
                                    ->label(__('data::fields.symbol'))
                                    ->maxLength(20),
                                Textarea::make('description')
                                    ->label(__('data::fields.description'))
                                    ->columnSpanFull(),
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
                TextColumn::make('code')
                    ->label(__('data::fields.code'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('common_name')
                    ->label(__('data::fields.common_name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('symbol')
                    ->label(__('data::fields.symbol'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('description')
                    ->label(__('data::fields.description'))
                    ->limit(80)
                    ->wrap(),
            ])
            ->defaultSort('common_name', 'asc')
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
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaticUnits::route('/'),
            'create' => CreateStaticUnit::route('/create'),
            'edit' => EditStaticUnit::route('/{record}/edit'),
            'view' => ViewStaticUnit::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function hasRecordTitle(): bool
    {
        return true;
    }

    public static function getRecordTitle(?Model $record): string
    {
        if (! $record instanceof StaticUnit) {
            return '';
        }

        /** @var StaticUnit $record */
        $symbol = filled($record->symbol) ? (string) $record->symbol : null;
        $name = filled($record->common_name) ? (string) $record->common_name : null;

        if ($symbol !== null && $name !== null) {
            return "{$symbol} — {$name}";
        }

        if ($symbol !== null) {
            return $symbol;
        }

        if ($name !== null) {
            return $name;
        }

        if (filled($record->code)) {
            return (string) $record->code;
        }

        return (string) $record->getKey();
    }
}
