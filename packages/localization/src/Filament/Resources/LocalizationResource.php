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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Moox\Core\Entities\Items\Record\BaseRecordResource;
use Moox\Data\Models\StaticLanguage;
use Moox\Data\Models\StaticLocale;
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
                                    ->relationship('language', 'common_name')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $language = StaticLanguage::find($state);
                                            if ($language) {
                                                $set('locale_variant', $language->alpha2);
                                            }
                                        }
                                    }),
                                TextInput::make('title')
                                    ->label(__('localization::fields.title'))
                                    ->required()
                                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                                TextInput::make('slug')
                                    ->label(__('localization::fields.slug'))
                                    ->required(),
                                Select::make('locale_variant')
                                    ->label('Locale Variant')
                                    ->options(function ($get) {
                                        $languageId = $get('language_id');
                                        if (! $languageId) {
                                            return [];
                                        }

                                        $language = StaticLanguage::find($languageId);
                                        if (! $language) {
                                            return [];
                                        }

                                        $baseLanguage = $language->alpha2;

                                        $locales = StaticLocale::where('language_id', $languageId)->with('country')->get();

                                        $options = [
                                            $baseLanguage => $language->common_name.' ('.$baseLanguage.')',
                                        ];

                                        foreach ($locales as $locale) {
                                            $countryName = $locale->country ? $locale->country->common_name : 'Unknown';
                                            $options[$locale->locale] = $language->common_name.' ('.$countryName.')';
                                        }

                                        return $options;
                                    })
                                    ->required(),
                                Select::make('fallback_language_id')
                                    ->label(__('localization::fields.fallback_language'))
                                    ->relationship('fallbackLanguage', 'title')
                                    ->nullable(),
                                Toggle::make('is_active_admin')
                                    ->label(__('localization::fields.is_activ_admin'))
                                    ->default(true),
                                Toggle::make('is_active_frontend')
                                    ->label(__('localization::fields.is_activ_frontend'))
                                    ->default(false),
                                Toggle::make('is_default')
                                    ->label(__('localization::fields.is_default'))
                                    ->default(false)
                                    ->afterStateUpdated(function ($state, $set, $get, $livewire) {
                                        if ($state) {
                                            $currentRecordId = $livewire->record?->id;
                                            $languageId = $get('language_id');

                                            Localization::where('language_id', $languageId)
                                                ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                                    $query->where('id', '!=', $currentRecordId);
                                                })
                                                ->update(['is_default' => false]);
                                        }
                                    }),
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
                                            ->required()
                                            ->options(__('localization::enums/fallback-behaviour'))
                                            ->default('default'),
                                    ]),
                                Section::make('')
                                    ->schema([
                                        Select::make('language_routing')
                                            ->label(__('localization::fields.language_routing'))
                                            ->required()
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
                IconColumn::make('table_flag')
                    ->label('Flag')
                    ->icon(fn (string $state): string => $state),
                TextColumn::make('display_name')
                    ->label(__('localization::fields.language'))
                    ->searchable(),
                TextColumn::make('locale_variant')
                    ->label('Locale Variant'),
                TextColumn::make('display_name')
                    ->label(__('localization::fields.title'))
                    ->searchable(),
                TextColumn::make('slug')
                    ->label(__('localization::fields.slug')),
                TextColumn::make('fallbackLanguage.title')
                    ->label(__('localization::fields.fallback_language')),
                ToggleColumn::make('is_active_admin')
                    ->label(__('localization::fields.is_activ_admin'))
                    ->afterStateUpdated(function ($state, $record) {
                        $settings = $record->language_settings ?? [];

                        if ($state && str_contains($record->locale, '_')) {
                            $settings['show_regional_variants'] = true;
                        } elseif (! $state) {
                            $settings['use_native_names'] = false;
                            $settings['show_regional_variants'] = false;
                            $settings['use_country_translations'] = false;
                        }

                        $record->update(['language_settings' => $settings]);
                    }),
                ToggleColumn::make('is_active_frontend')
                    ->label(__('localization::fields.is_activ_frontend'))
                    ->afterStateUpdated(function ($state, $record) {
                        $settings = $record->language_settings ?? [];

                        if ($state && str_contains($record->locale, '_')) {
                            $settings['show_regional_variants'] = true;
                        } elseif (! $state) {
                            $settings['use_native_names'] = false;
                            $settings['show_regional_variants'] = false;
                            $settings['use_country_translations'] = false;
                        }

                        $record->update(['language_settings' => $settings]);
                    }),
                ToggleColumn::make('is_default')
                    ->label(__('localization::fields.is_default'))
                    ->afterStateUpdated(function ($state, $record) {
                        if ($state) {
                            static::getModel()::where('id', '!=', $record->id)
                                ->update(['is_default' => false]);
                        }
                    }),
                ToggleColumn::make('language_settings->use_native_names')
                    ->label(__('localization::fields.use_native_names'))
                    ->getStateUsing(function ($record) {
                        return $record->getLanguageSetting('use_native_names');
                    })
                    ->afterStateUpdated(function ($state, $record) {
                        $settings = $record->language_settings ?? [];
                        $settings['use_native_names'] = $state;
                        $record->update(['language_settings' => $settings]);
                    }),
                ToggleColumn::make('language_settings->show_regional_variants')
                    ->label(__('localization::fields.show_regional_variants'))
                    ->getStateUsing(function ($record) {
                        return $record->getLanguageSetting('show_regional_variants');
                    })
                    ->afterStateUpdated(function ($state, $record) {
                        $settings = $record->language_settings ?? [];
                        $settings['show_regional_variants'] = $state;
                        $record->update(['language_settings' => $settings]);
                    }),
                ToggleColumn::make('language_settings->use_country_translations')
                    ->label(__('localization::fields.use_country_translations'))
                    ->getStateUsing(function ($record) {
                        return $record->getLanguageSetting('use_country_translations');
                    })
                    ->afterStateUpdated(function ($state, $record) {
                        $settings = $record->language_settings ?? [];
                        $settings['use_country_translations'] = $state;
                        $record->update(['language_settings' => $settings]);
                    }),
                TextColumn::make('fallback_behaviour')
                    ->label(__('localization::fields.fallback_behaviour')),
                TextColumn::make('language_routing')
                    ->label(__('localization::fields.language_routing')),
                TextColumn::make('translation_status')
                    ->label(__('localization::fields.translation_status')),
            ])
            ->groups([
                Group::make('language.common_name')
                    ->label(__('localization::fields.language'))
                    ->getTitleFromRecordUsing(fn (Localization $record): string => $record->language->common_name)
                    ->collapsible(),
            ])
            ->defaultGroup('language.common_name')
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
