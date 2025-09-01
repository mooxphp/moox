<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\Localization\Filament\Resources\LocalizationResource\Pages\CreateLocalization;
use Moox\Localization\Filament\Resources\LocalizationResource\Pages\EditLocalization;
use Moox\Localization\Filament\Resources\LocalizationResource\Pages\ListLocalizations;
use Moox\Localization\Filament\Resources\LocalizationResource\Pages\ViewLocalization;
use Moox\Localization\Models\Localization;

class LocalizationResource extends BaseRecordResource
{
    protected static ?string $model = Localization::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-language';

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                Select::make('language_id')
                                    ->label(__('localization::fields.language'))
                                    ->relationship('language', 'alpha2')
                                    ->required()
                                    ->live(),
                                TextInput::make('title')
                                    ->label(__('localization::fields.title'))
                                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                                TextInput::make('slug')
                                    ->label(__('localization::fields.slug')),
                                Select::make('fallback_language_id')
                                    ->label(__('localization::fields.fallback_language'))
                                    ->relationship('fallbackLanguage', 'title')
                                    ->nullable(),
                                Toggle::make('is_active_admin')
                                    ->label(__('localization::fields.is_activ_admin'))
                                    ->default(false),
                                Toggle::make('is_active_frontend')
                                    ->label(__('localization::fields.is_activ_frontend'))
                                    ->default(false),
                                Toggle::make('is_default')
                                    ->label(__('localization::fields.is_default'))
                                    ->default(false),
                                TextInput::make('routing_path')
                                    ->label(__('localization::fields.routing_path'))
                                    ->nullable(),
                                TextInput::make('routing_subdomain')
                                    ->label(__('localization::fields.routing_subdomain'))
                                    ->nullable(),
                                TextInput::make('routing_domain')
                                    ->label(__('localization::fields.routing_domain'))
                                    ->nullable(),
                                TextInput::make('translation_status')
                                    ->label(__('localization::fields.translation_status'))
                                    ->numeric()
                                    ->nullable(),
                                Textarea::make('language_settings')
                                    ->label(__('localization::fields.language_settings'))
                                    ->json(),
                            ])
                            ->columnSpan(2),
                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        static::getFormActions(),
                                    ]),
                                Section::make('')
                                    ->schema([
                                        Select::make('fallback_behaviour')
                                            ->label(__('localization::fields.fallback_behaviour'))
                                            ->options(__('localization::enums/fallback-behaviour'))
                                            ->default('default'),
                                    ]),
                                Section::make('')
                                    ->schema([
                                        Select::make('language_routing')
                                            ->label(__('localization::fields.language_routing'))
                                            ->options(__('localization::enums/language-routing'))
                                            ->default('path'),
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
                TextColumn::make('language.common_name')
                    ->label(__('localization::fields.language')),
                TextColumn::make('title')
                    ->label(__('localization::fields.title')),
                TextColumn::make('slug')
                    ->label(__('localization::fields.slug')),
                TextColumn::make('fallbackLanguage.title')
                    ->label(__('localization::fields.fallback_language')),
                ToggleColumn::make('is_active_admin')
                    ->label(__('localization::fields.is_activ_admin')),
                ToggleColumn::make('is_active_frontend')
                    ->label(__('localization::fields.is_activ_frontend')),
                ToggleColumn::make('is_default')
                    ->label(__('localization::fields.is_default')),
                TextColumn::make('fallback_behaviour')
                    ->label(__('localization::fields.fallback_behaviour')),
                TextColumn::make('language_routing')
                    ->label(__('localization::fields.language_routing')),
                TextColumn::make('translation_status')
                    ->label(__('localization::fields.translation_status')),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocalizations::route('/'),
            'create' => CreateLocalization::route('/create'),
            'edit' => EditLocalization::route('/{record}/edit'),
            'view' => ViewLocalization::route('/{record}'),
        ];
    }
}
