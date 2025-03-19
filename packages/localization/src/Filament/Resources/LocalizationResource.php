<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;
use Moox\Localization\Filament\Resources\LocalizationResource\Pages;

class LocalizationResource extends Resource
{
    use BaseInResource, SingleSimpleInResource;

    protected static ?string $model = \Moox\Localization\Models\Localization::class;

    protected static ?string $navigationIcon = 'gmdi-language';

    public static function getModelLabel(): string
    {
        return config('localization.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('localization.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('localization.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('localization.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('localization.navigation_group');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Grid::make()->schema([
                            Section::make([
                                Forms\Components\Select::make('language_id')
                                    ->label(__('localization::fields.language'))
                                    ->relationship('language', 'alpha2')
                                    ->required()
                                    ->live(),
                                Forms\Components\TextInput::make('title')
                                    ->label(__('localization::fields.title'))
                                    ->afterStateUpdated(fn (\Filament\Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state))),
                                Forms\Components\TextInput::make('slug')
                                    ->label(__('localization::fields.slug')),
                                Forms\Components\Select::make('fallback_language_id')
                                    ->label(__('localization::fields.fallback_language'))
                                    ->relationship('fallbackLanguage', 'title')
                                    ->nullable(),
                                Forms\Components\Toggle::make('is_active_admin')
                                    ->label(__('localization::fields.is_activ_admin'))
                                    ->default(false),

                                Forms\Components\Toggle::make('is_active_frontend')
                                    ->label(__('localization::fields.is_activ_frontend'))
                                    ->default(false),

                                Forms\Components\Toggle::make('is_default')
                                    ->label(__('localization::fields.is_default'))
                                    ->default(false),

                                Forms\Components\TextInput::make('routing_path')
                                    ->label(__('localization::fields.routing_path'))
                                    ->nullable(),

                                Forms\Components\TextInput::make('routing_subdomain')
                                    ->label(__('localization::fields.routing_subdomain'))
                                    ->nullable(),

                                Forms\Components\TextInput::make('routing_domain')
                                    ->label(__('localization::fields.routing_domain'))
                                    ->nullable(),

                                Forms\Components\TextInput::make('translation_status')
                                    ->label(__('localization::fields.translation_status'))
                                    ->numeric()
                                    ->nullable(),

                                Forms\Components\Textarea::make('language_settings')
                                    ->label(__('localization::fields.language_settings'))
                                    ->json(),
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
                                        Forms\Components\Select::make('fallback_behaviour')
                                            ->label(__('localization::fields.fallback_behaviour'))
                                            ->options(__('localization::enums/fallback-behaviour'))
                                            ->default('default'),
                                    ]),
                                Section::make('')
                                    ->schema([
                                        Forms\Components\Select::make('language_routing')
                                            ->label(__('localization::fields.language_routing'))
                                            ->options(__('localization::enums/language-routing'))
                                            ->default('path'),
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
                Tables\Columns\TextColumn::make('language.common_name')
                    ->label(__('localization::fields.language')),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('localization::fields.title')),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('localization::fields.slug')),
                Tables\Columns\TextColumn::make('fallbackLanguage.title')
                    ->label(__('localization::fields.fallback_language')),
                Tables\Columns\ToggleColumn::make('is_active_admin')
                    ->label(__('localization::fields.is_activ_admin')),
                Tables\Columns\ToggleColumn::make('is_active_frontend')
                    ->label(__('localization::fields.is_activ_frontend')),
                Tables\Columns\ToggleColumn::make('is_default')
                    ->label(__('localization::fields.is_default')),
                Tables\Columns\TextColumn::make('fallback_behaviour')
                    ->label(__('localization::fields.fallback_behaviour')),
                Tables\Columns\TextColumn::make('language_routing')
                    ->label(__('localization::fields.language_routing')),
                Tables\Columns\TextColumn::make('translation_status')
                    ->label(__('localization::fields.translation_status')),
            ])
            ->defaultSort('id', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocalizations::route('/'),
            'create' => Pages\CreateLocalization::route('/create'),
            'edit' => Pages\EditLocalization::route('/{record}/edit'),
            'view' => Pages\ViewLocalization::route('/{record}'),
        ];
    }
}
