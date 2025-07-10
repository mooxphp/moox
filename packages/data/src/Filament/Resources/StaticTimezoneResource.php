<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Entities\Items\Draft\BaseDraftResource;
use Moox\Data\Filament\Resources\StaticTimezoneResource\Pages\CreateStaticTimezone;
use Moox\Data\Filament\Resources\StaticTimezoneResource\Pages\EditStaticTimezone;
use Moox\Data\Filament\Resources\StaticTimezoneResource\Pages\ListStaticTimezones;
use Moox\Data\Filament\Resources\StaticTimezoneResource\Pages\ViewStaticTimezone;
use Moox\Data\Filament\Resources\StaticTimezoneResource\RelationManagers\StaticCountriesRelationManager;
use Moox\Data\Models\StaticTimezone;

class StaticTimezoneResource extends BaseDraftResource
{
    protected static ?string $model = StaticTimezone::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-travel-explore-o';

    public static function getModelLabel(): string
    {
        return config('static-timezone.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('static-timezone.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('static-timezone.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('static-timezone.single');
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
                                TextInput::make('name')
                                    ->label(__('data::fields.name'))
                                    ->maxLength(255)
                                    ->required(),
                                TextInput::make('offset_standard')
                                    ->label(__('data::fields.offset_standard'))
                                    ->maxLength(6)->required(),
                                Toggle::make('dst')
                                    ->label(__('data::fields.dst'))->required(),
                                DateTimePicker::make('dst_start')
                                    ->label(__('data::fields.dst_start')),
                                DateTimePicker::make('dst_end')
                                    ->label(__('data::fields.dst_end')),
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
                TextColumn::make('name')
                    ->label(__('data::fields.name')),
                TextColumn::make('offset_standard')
                    ->label(__('data::fields.offset_standard')),
                IconColumn::make('dst')
                    ->label(__('data::fields.dst'))
                    ->boolean(),
                TextColumn::make('dst_start')
                    ->label(__('data::fields.dst_start'))
                    ->datetime(),
                TextColumn::make('dst_end')
                    ->label(__('data::fields.dst_end'))
                    ->datetime(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                Filter::make('name')
                    ->schema([
                        TextInput::make('name')
                            ->label(__('data::fields.name'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['name'],
                            fn (Builder $query, $value): Builder => $query->where('name', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['name']) {
                            return null;
                        }

                        return 'name: '.$data['name'];
                    }),
                Filter::make('offset_standart')
                    ->schema([
                        TextInput::make('offset_standart')
                            ->label(__('data::fields.offset_standard'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['offset_standart'],
                            fn (Builder $query, $value): Builder => $query->where('offset_standart', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['offset_standart']) {
                            return null;
                        }

                        return 'name: '.$data['offset_standart'];
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [StaticCountriesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaticTimezones::route('/'),
            'create' => CreateStaticTimezone::route('/create'),
            'edit' => EditStaticTimezone::route('/{record}/edit'),
            'view' => ViewStaticTimezone::route('/{record}'),
        ];
    }
}
