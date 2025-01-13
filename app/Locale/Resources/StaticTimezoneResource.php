<?php

declare(strict_types=1);

namespace App\Locale\Resources;

use App\Locale\Resources\StaticTimezoneResource\Pages;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;

class StaticTimezoneResource extends Resource
{
    use BaseInResource, SingleSimpleInResource;

    protected static ?string $model = \App\Locale\Models\StaticTimezone::class;

    protected static ?string $navigationIcon = 'gmdi-travel-explore-o';

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
                                    TextInput::make('name')
                                        ->label(__('entities/static-timezone.name'))
                                        ->maxLength(255)
                                        ->required(),
                                    TextInput::make('offset_standard')
                                        ->label(__('entities/static-timezone.offset_standard'))
                                        ->maxLength(6)->required(),
                                    Toggle::make('dst')
                                        ->label(__('entities/static-timezone.dst'))->required(),
                                    DateTimePicker::make('dst_start')
                                        ->label(__('entities/static-timezone.dst_start')),
                                    DateTimePicker::make('dst_end')
                                        ->label(__('entities/static-timezone.dst_end')),
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
                TextColumn::make('name')
                    ->label(__('entities/static-timezone.name')),
                TextColumn::make('offset_standard')
                    ->label(__('entities/static-timezone.offset_standard')),
                IconColumn::make('dst')
                    ->label(__('entities/static-timezone.dst'))
                    ->boolean(),
                TextColumn::make('dst_start')
                    ->label(__('entities/static-timezone.dst_start'))
                    ->datetime(),
                TextColumn::make('dst_end')
                    ->label(__('entities/static-timezone.dst_end'))
                    ->datetime(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                Filter::make('name')
                    ->form([
                        TextInput::make('name')
                            ->label(__('entities/static-timezone.name'))
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
                    ->form([
                        TextInput::make('offset_standart')
                            ->label(__('entities/static-timezone.offset_standard'))
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaticTimezones::route('/'),
            'create' => Pages\CreateStaticTimezone::route('/create'),
            'edit' => Pages\EditStaticTimezone::route('/{record}/edit'),
            'view' => Pages\ViewStaticTimezone::route('/{record}'),
        ];
    }
}
