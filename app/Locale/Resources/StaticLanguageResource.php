<?php

declare(strict_types=1);

namespace App\Locale\Resources;

use App\Forms\Components\JsonField;
use App\Locale\Resources\StaticLanguageResource\Pages;
use Filament\Forms\Components\Grid;
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

class StaticLanguageResource extends Resource
{
    use BaseInResource, SingleSimpleInResource;

    protected static ?string $model = \App\Locale\Models\StaticLanguage::class;

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
        return config('static-language.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('static-language.navigation_sort') + 1;
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
                                        ->label(__('locale.alpha2'))
                                        ->maxLength(255)->required(),
                                    TextInput::make('alpha3_b')
                                        ->label(__('locale.alpha3_b'))
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('alpha3_t')
                                        ->label(__('locale.alpha3_t'))
                                        ->maxLength(255)->nullable(),
                                    TextInput::make('common_name')
                                        ->label(__('locale.common_name'))
                                        ->maxLength(255)->required(),
                                    TextInput::make('native_name')
                                        ->label(__('locale.native_name'))
                                        ->maxLength(255)->nullable(),
                                    JsonField::make('exonyms')->label(__('locale.exonyms')),
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
                                        ->label(__('entities/static-language.script'))
                                        ->options(__('entities/static-language.script_options'))
                                        ->required(),
                                ]),
                            Section::make('')
                                ->schema([
                                    Select::make('direction')
                                        ->label(__('entities/static-language.direction'))
                                        ->options(__('entities/static-language.direction_options'))
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
                TextColumn::make('alpha2'),
                TextColumn::make('alpha3_b'),
                TextColumn::make('alpha3_t'),
                TextColumn::make('common_name')
                    ->getStateUsing(function ($record) {
                        $locale = app()->getLocale();

                        return $record->exonyms[$locale] ?? $record->name;
                    }),
                TextColumn::make('native_name'),
                TextColumn::make('script')->sortable()->searchable()->toggleable(),
                TextColumn::make('direction')->sortable()->searchable()->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
            ->filters([
                Filter::make('alpha2')
                    ->form([
                        TextInput::make('alpha2')
                            ->label('Alpha-2 Code')
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
                            ->label('Alpha-3 Bibliographic Code')
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
                            ->label('Alpha-3 Terminology Code')
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
                Filter::make('native_name')
                    ->form([
                        TextInput::make('native_name')
                            ->label('Native Name')
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
                    ->label('Script')
                    ->placeholder(__('core::core.filter').' Script')
                    ->options(['Latin' => 'Latin', 'Cyrillic' => 'Cyrillic', 'Arabic' => 'Arabic', 'Devanagari' => 'Devanagari', 'Other' => 'Other']),
                SelectFilter::make('direction')
                    ->label('Direction')
                    ->placeholder(__('core::core.filter').' Direction')
                    ->options(['LTR' => 'LTR', 'RTL' => 'RTL']),
            ]);
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
