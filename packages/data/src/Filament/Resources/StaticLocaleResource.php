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
use Moox\Data\Filament\Resources\StaticLocaleResource\Pages;

class StaticLocaleResource extends Resource
{
    use BaseInResource, HasResourceTabs, SingleSimpleInResource;

    protected static ?string $model = \Moox\Data\Models\StaticLocale::class;

    protected static ?string $navigationIcon = 'gmdi-fmd-good-s';

    public static function getModelLabel(): string
    {
        return config('static-locale.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('static-locale.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('static-locale.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('static-locale.single');
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
                                    Select::make('language_id')
                                        ->label(__('data::fields.language'))
                                        ->relationship('language', 'common_name')
                                        ->searchable()
                                        ->preload()->required(),
                                    Select::make('country_id')
                                        ->label(__('data::fields.country'))
                                        ->relationship('country', 'common_name')
                                        ->searchable()
                                        ->preload()->required(),
                                    Toggle::make('is_official_language')
                                        ->label(__('data::fields.is_official_language'))
                                        ->default(false),
                                    TextInput::make('locale')
                                        ->label(__('data::fields.locale'))
                                        ->maxLength(255)->required(),
                                    TextInput::make('name')
                                        ->label(__('data::fields.name'))
                                        ->maxLength(255)->required(),

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
                TextColumn::make('locale')
                    ->label(__('data::fields.locale'))->sortable()->searchable(),
                TextColumn::make('name')->label(__('data::fields.name'))->sortable()->searchable()->toggleable(),
                IconColumn::make('language_flag_icon')
                    ->label('')
                    ->icon(fn (string $state): string => $state),
                TextColumn::make('language.common_name')
                    ->label(__('data::fields.common_language_name'))
                    ->sortable()->searchable(),
                IconColumn::make('country_flag_icon')
                    ->label('')
                    ->icon(fn (string $state): string => $state),
                TextColumn::make('country.common_name')
                    ->label(__('data::fields.common_country_name'))
                    ->sortable()->searchable(),
                IconColumn::make('is_official_language')
                    ->label(__('data::fields.is_official_language'))
                    ->boolean(),
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
                Filter::make('language_id')
                    ->form([
                        TextInput::make('language_id')
                            ->label(__('data::fields.language_id'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['language_id'],
                            fn (Builder $query, $value): Builder => $query->where('language_id', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['language_id']) {
                            return null;
                        }

                        return 'Language ID: '.$data['language_id'];
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
                Filter::make('locale')
                    ->form([
                        TextInput::make('locale')
                            ->label(__('data::fields.locale'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['locale'],
                            fn (Builder $query, $value): Builder => $query->where('locale', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['locale']) {
                            return null;
                        }

                        return 'Locale: '.$data['locale'];
                    }),
                Filter::make('name')
                    ->form([
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

                        return 'Name: '.$data['name'];
                    }),
                SelectFilter::make('language')
                    ->label(__('data::fields.language'))
                    ->relationship('language', 'common_name'),
                SelectFilter::make('country')
                    ->label(__('data::fields.country'))
                    ->relationship('country', 'common_name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaticLocales::route('/'),
            'create' => Pages\CreateStaticLocale::route('/create'),
            'edit' => Pages\EditStaticLocale::route('/{record}/edit'),
            'view' => Pages\ViewStaticLocale::route('/{record}'),
        ];
    }
}
