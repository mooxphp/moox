<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;
use Moox\Data\Filament\Resources\StaticLanguageResource\Pages;
use Moox\Data\Filament\Resources\StaticLanguageResource\RelationManagers\StaticLocalesRelationManager;

class StaticLanguageResource extends Resource
{
    use BaseInResource, SingleSimpleInResource;

    protected static ?string $model = \Moox\Data\Models\StaticLanguage::class;

    protected static ?string $navigationIcon = 'gmdi-language';

    public static function getModelLabel(): string
    {
        return config('static-language.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('static-language.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('static-language.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('static-language.single');
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
                                    TextInput::make('alpha2')
                                        ->label(__('data::fields.alpha2'))
                                        ->maxLength(255)->required(),
                                    TextInput::make('alpha3_b')
                                        ->label(__('data::fields.alpha3_b'))
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('alpha3_t')
                                        ->label(__('data::fields.alpha3_t'))
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('common_name')
                                        ->label(__('data::fields.common_name'))
                                        ->maxLength(255)->required(),
                                    TextInput::make('native_name')
                                        ->label(__('data::fields.native_name'))
                                        ->maxLength(255)->nullable(),
                                    KeyValue::make('exonyms')->label(__('data::fields.exonyms')),
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
                                    Select::make('script')
                                        ->label(__('data::fields.script'))
                                        ->options(__('data::enums/language-script'))
                                        ->required(),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('direction')
                                        ->label(__('data::fields.direction'))
                                        ->options(__('data::enums/language-direction'))
                                        ->required(),
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
                TextColumn::make('alpha2')
                    ->label(__('data::fields.alpha2')),
                TextColumn::make('alpha3_b')
                    ->label(__('data::fields.alpha3_b')),
                TextColumn::make('alpha3_t')
                    ->label(__('data::fields.alpha3_t')),
                TextColumn::make('common_name')
                    ->label(__('data::fields.common_name')),
                TextColumn::make('native_name')
                    ->label(__('data::fields.native_name')),
                TextColumn::make('script')->sortable()->searchable()->toggleable()
                    ->label(__('data::fields.script')),
                TextColumn::make('direction')->sortable()->searchable()->toggleable()
                    ->label(__('data::fields.direction')),
            ])
            ->defaultSort('id', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                Filter::make('alpha2')
                    ->form([
                        TextInput::make('alpha2')
                            ->label(__('data::fields.alpha2'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['alpha2'],
                            fn (Builder $query, $value): Builder => $query->where('alpha2', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['alpha2']) {
                            return null;
                        }

                        return 'Alpha-2 Code: '.$data['alpha2'];
                    }),
                Filter::make('alpha3_b')
                    ->form([
                        TextInput::make('alpha3_b')
                            ->label(__('data::fields.alpha3_b'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['alpha3_b'],
                            fn (Builder $query, $value): Builder => $query->where('alpha3_b', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['alpha3_b']) {
                            return null;
                        }

                        return 'Alpha-3 Bibliographic Code: '.$data['alpha3_b'];
                    }),
                Filter::make('alpha3_t')
                    ->form([
                        TextInput::make('alpha3_t')
                            ->label(__('data::fields.alpha3_t'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['alpha3_t'],
                            fn (Builder $query, $value): Builder => $query->where('alpha3_t', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['alpha3_t']) {
                            return null;
                        }

                        return 'Alpha-3 Terminology Code: '.$data['alpha3_t'];
                    }),
                Filter::make('common_name')
                    ->form([
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
                Filter::make('native_name')
                    ->form([
                        TextInput::make('native_name')
                            ->label(__('data::fields.native_name'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['native_name'],
                            fn (Builder $query, $value): Builder => $query->where('native_name', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['native_name']) {
                            return null;
                        }

                        return 'Native Name: '.$data['native_name'];
                    }),
                SelectFilter::make('script')
                    ->label(__('data::fields.script'))
                    ->placeholder(__('core::core.filter').' Script')
                    ->options(__('data::enums/language-script')),
                SelectFilter::make('direction')
                    ->label(__('data::fields.direction'))
                    ->placeholder(__('core::core.filter').' Direction')
                    ->options(__('data::enums/language-direction')),
            ]);
    }

    public static function getRelations(): array
    {
        return [StaticLocalesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaticLanguages::route('/'),
            'create' => Pages\CreateStaticLanguage::route('/create'),
            'edit' => Pages\EditStaticLanguage::route('/{record}/edit'),
            'view' => Pages\ViewStaticLanguage::route('/{record}'),
        ];
    }
}
