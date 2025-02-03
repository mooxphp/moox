<?php

declare(strict_types=1);

namespace Moox\DataLanguages\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Moox\Core\Traits\Base\BaseInResource;
use Moox\Core\Traits\Simple\SingleSimpleInResource;
use Moox\DataLanguages\Resources\LocalizationResource\Pages;

class LocalizationResource extends Resource
{
    use BaseInResource, SingleSimpleInResource;

    protected static ?string $model = \Moox\DataLanguages\Models\Localization::class;

    protected static ?string $navigationIcon = 'gmdi-language';

    public static function getModelLabel(): string
    {
        return __('data-languages::localization.localization');
    }

    public static function getPluralModelLabel(): string
    {
        return __('data-languages::localization.localizations');
    }

    public static function getNavigationLabel(): string
    {
        return __('data-languages::localization.localizations');
    }

    public static function getBreadcrumb(): string
    {
        return __('data-languages::localization.localization');
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
        return $form
        ->schema([
            Grid::make(2)
            ->schema([
                Grid::make()->schema([
                    Section::make([
                        Forms\Components\Select::make('language_id')
                        ->label(__('data-languages::localization.language'))
                        ->relationship('language', 'alpha2')
                        ->required()
                        ->live(),

                        Forms\Components\TextInput::make('title')
                            ->label(__('data-languages::localization.title'))
                        ->afterStateUpdated(fn (\Filament\Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state))),
                         Forms\Components\TextInput::make('slug')
                            ->label(__('data-languages::localization.slug')),
                        Forms\Components\Select::make('fallback_language_id')
                        ->label(__('data-languages::localization.fallback_language'))
                        ->relationship('fallbackLanguage', 'id')
                        ->nullable(),
                        Forms\Components\Toggle::make('is_active_admin')
                            ->label(__('data-languages::localization.is_activ_admin'))
                            ->default(false),

                            Forms\Components\Toggle::make('is_active_frontend')
                            ->label(__('data-languages::localization.is_activ_frontend'))
                            ->default(false),

                            Forms\Components\Toggle::make('is_default')
                            ->label(__('data-languages::localization.is_default'))
                            ->default(false),

                        Forms\Components\TextInput::make('routing_path')
                            ->nullable(),

                        Forms\Components\TextInput::make('routing_subdomain')
                            ->nullable(),

                        Forms\Components\TextInput::make('routing_domain')
                            ->nullable(),

                        Forms\Components\TextInput::make('translation_status')
                            ->numeric()
                            ->nullable(),

                        Forms\Components\Textarea::make('language_settings')
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
                                    ->label(__('data-languages::localization.fallback_behaviour'))
                                    ->options(__('data-languages::localization.fallback_behaviour_options'))
                                    ->default('default'),
                                ]),
                            Section::make('')
                                ->schema([
                                    Forms\Components\Select::make('language_routing')
                                    ->label(__('data-languages::localization.language_routing'))
                                    ->options(__('data-languages::localization.language_routing_options'))
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
                ->label(__('data-languages::localization.language')),
                Tables\Columns\TextColumn::make('title')
                ->label(__('data-languages::localization.title')),
                Tables\Columns\TextColumn::make('slug')
                ->label(__('data-languages::localization.slug')),
                Tables\Columns\TextColumn::make('fallbackLanguage.id')
                ->label(__('data-languages::localization.fallback_language')),
                Tables\Columns\ToggleColumn::make('is_active_admin')
                ->label(__('data-languages::localization.is_activ_admin')),
                Tables\Columns\ToggleColumn::make('is_active_frontend')
                ->label(__('data-languages::localization.is_activ_frontend')),
                Tables\Columns\ToggleColumn::make('is_default')
                ->label(__('data-languages::localization.is_default')),
                Tables\Columns\TextColumn::make('fallback_behaviour')
                ->label(__('data-languages::localization.fallback_behaviour')),
                Tables\Columns\TextColumn::make('language_routing')
                ->label(__('data-languages::localization.language_routing')),
                Tables\Columns\TextColumn::make('translation_status')
                ->label(__('data-languages::localization.translation_status')),
            ])
            ->defaultSort('id', 'desc')
            ->actions([...static::getTableActions()])
            ->bulkActions([...static::getBulkActions()])
           ;
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
