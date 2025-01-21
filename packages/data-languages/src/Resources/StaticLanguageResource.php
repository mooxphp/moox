<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Resources;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Forms\Components\JsonField;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;
use Moox\DataLanguages\Resources\StaticLanguageResource\Pages;
use Moox\DataLanguages\Resources\StaticLanguageResource\RelationManagers\StaticLocalesRelationManager;

class StaticLanguageResource extends Resource
{
    use BaseInResource, SingleSimpleInResource;

    protected static ?string $model = \Moox\DataLanguages\Models\StaticLanguage::class;

    protected static ?string $navigationIcon = 'gmdi-language';

    public static function getModelLabel(): string
    {
        return __('data-languages::static-language.static_language');
    }

    public static function getPluralModelLabel(): string
    {
        return __('data-languages::static-language.static_languages');
    }

    public static function getNavigationLabel(): string
    {
        return __('data-languages::static-language.static_languages');
    }

    public static function getBreadcrumb(): string
    {
        return __('data-languages::static-language.static_language');
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
                                    TextInput::make('alpha2')
                                        ->label(__('data-languages::data-languages.alpha2'))
                                        ->maxLength(255)->required(),
                                    TextInput::make('alpha3_b')
                                        ->label(__('data-languages::data-languages.alpha3_b'))
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('alpha3_t')
                                        ->label(__('data-languages::data-languages.alpha3_t'))
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('common_name')
                                        ->label(__('data-languages::data-languages.common_name'))
                                        ->maxLength(255)->required(),
                                    TextInput::make('native_name')
                                        ->label(__('data-languages::data-languages.native_name'))
                                        ->maxLength(255)->nullable(),
                                    JsonField::make('exonyms')->label(__('data-languages::data-languages.exonyms')),
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
                                        ->label(__('data-languages::static-language.script'))
                                        ->options(__('data-languages::static-language.script_options'))
                                        ->required(),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('direction')
                                        ->label(__('data-languages::static-language.direction'))
                                        ->options(__('data-languages::static-language.direction_options'))
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
                    ->label(__('data-languages::data-languages.alpha2')),
                TextColumn::make('alpha3_b')
                    ->label(__('data-languages::data-languages.alpha3_b')),
                TextColumn::make('alpha3_t')
                    ->label(__('data-languages::data-languages.alpha3_t')),
                TextColumn::make('common_name')
                    ->label(__('data-languages::data-languages.common_name')),
                TextColumn::make('native_name')
                    ->label(__('data-languages::data-languages.native_name')),
                TextColumn::make('script')->sortable()->searchable()->toggleable()
                    ->label(__('data-languages::static-language.script')),
                TextColumn::make('direction')->sortable()->searchable()->toggleable()
                    ->label(__('data-languages::static-language.direction')),
            ])
            ->defaultSort('id', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                Filter::make('alpha2')
                    ->form([
                        TextInput::make('alpha2')
                            ->label(__('data-languages::data-languages.alpha2'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['alpha2'],
                            fn(Builder $query, $value): Builder => $query->where('alpha2', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['alpha2']) {
                            return null;
                        }

                        return 'Alpha-2 Code: ' . $data['alpha2'];
                    }),
                Filter::make('alpha3_b')
                    ->form([
                        TextInput::make('alpha3_b')
                            ->label(__('data-languages::data-languages.alpha3_b'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['alpha3_b'],
                            fn(Builder $query, $value): Builder => $query->where('alpha3_b', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['alpha3_b']) {
                            return null;
                        }

                        return 'Alpha-3 Bibliographic Code: ' . $data['alpha3_b'];
                    }),
                Filter::make('alpha3_t')
                    ->form([
                        TextInput::make('alpha3_t')
                            ->label(__('data-languages::data-languages.alpha3_t'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['alpha3_t'],
                            fn(Builder $query, $value): Builder => $query->where('alpha3_t', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['alpha3_t']) {
                            return null;
                        }

                        return 'Alpha-3 Terminology Code: ' . $data['alpha3_t'];
                    }),
                Filter::make('common_name')
                    ->form([
                        TextInput::make('common_name')
                            ->label(__('data-languages::data-languages.common_name'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['common_name'],
                            fn(Builder $query, $value): Builder => $query->where('common_name', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['common_name']) {
                            return null;
                        }

                        return 'Common Name: ' . $data['common_name'];
                    }),
                Filter::make('native_name')
                    ->form([
                        TextInput::make('native_name')
                            ->label(__('data-languages::data-languages.native_name'))
                            ->placeholder(__('core::core.search')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['native_name'],
                            fn(Builder $query, $value): Builder => $query->where('native_name', 'like', "%{$value}%"),
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['native_name']) {
                            return null;
                        }

                        return 'Native Name: ' . $data['native_name'];
                    }),
                SelectFilter::make('script')
                    ->label(__('data-languages::static-language.script'))
                    ->placeholder(__('core::core.filter') . ' Script')
                    ->options(__('data-languages::static-language.script_options')),
                SelectFilter::make('direction')
                    ->label('Direction')
                    ->placeholder(__('core::core.filter') . ' Direction')
                    ->options(__('data-languages::static-language.direction_options')),
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
