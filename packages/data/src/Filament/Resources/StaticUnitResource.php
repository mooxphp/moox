<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Entities\Items\Static\BaseStaticResource;
use Moox\Data\Filament\Resources\StaticUnitResource\Pages\CreateStaticUnit;
use Moox\Data\Filament\Resources\StaticUnitResource\Pages\EditStaticUnit;
use Moox\Data\Filament\Resources\StaticUnitResource\Pages\ListStaticUnits;
use Moox\Data\Filament\Resources\StaticUnitResource\Pages\ViewStaticUnit;
use Moox\Data\Models\StaticUnit;
use Moox\Static\Filament\Resources\Concerns\HasStaticCodelistResource;

class StaticUnitResource extends BaseStaticResource
{
    use HasStaticCodelistResource;

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
                                TextInput::make('symbol')
                                    ->label(__('data::fields.symbol'))
                                    ->maxLength(20),
                                ...static::staticCodelistFormFields(),
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
            ->columns(static::staticCodelistTableColumns(extraColumns: [
                TextColumn::make('symbol')
                    ->label(__('data::fields.symbol'))
                    ->sortable()
                    ->searchable(),
            ]))
            ->defaultSort('code', 'asc')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                Filter::make('code')
                    ->schema([
                        TextInput::make('code')
                            ->label(__('data::fields.code'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['code'] ?? null,
                            fn (Builder $query, string $value): Builder => $query->where('code', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['code'])) {
                            return null;
                        }

                        return 'Code: '.$data['code'];
                    }),
                static::staticCodelistCommonNameFilter(),
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
