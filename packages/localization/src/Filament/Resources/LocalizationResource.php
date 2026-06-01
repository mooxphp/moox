<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Resources;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                                                // Try to find the first available locale
                                                $firstLocale = StaticLocale::where('language_id', $state)->first();
                                                if ($firstLocale) {
                                                    $set('locale_variant', $firstLocale->locale);
                                                } else {
                                                    // Fallback: en_US for English, otherwise Language_Language
                                                    if ($language->alpha2 === 'en') {
                                                        $set('locale_variant', 'en_US');
                                                    } else {
                                                        $set('locale_variant', $language->alpha2.'_'.strtoupper($language->alpha2));
                                                    }
                                                }
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

                                        $options = [];

                                        foreach ($locales as $locale) {
                                            $countryName = $locale->country ? $locale->country->common_name : 'Unknown';
                                            $options[$locale->locale] = $language->common_name.' ('.$countryName.')';
                                        }

                                        // If no locale exists in the DB, add a fallback
                                        if (empty($options)) {
                                            // en_US for English, otherwise Language_Language
                                            if ($baseLanguage === 'en') {
                                                $options['en_US'] = $language->common_name.' (Standard)';
                                            } else {
                                                $options[$baseLanguage.'_'.strtoupper($baseLanguage)] = $language->common_name.' (Standard)';
                                            }
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
                                    ->default(true)
                                    ->disabled(function ($get, $livewire) {
                                        $record = $livewire->record;
                                        $isDefault = $get('is_default') ?? ($record !== null ? $record->is_default : false);

                                        return $isDefault;
                                    }),
                                Toggle::make('is_active_frontend')
                                    ->label(__('localization::fields.is_activ_frontend'))
                                    ->default(false),
                                Toggle::make('is_default')
                                    ->label(__('localization::fields.is_default'))
                                    ->default(false)
                                    ->disabled(function ($get, $livewire) {
                                        $record = $livewire->record;
                                        $localeVariant = $get('locale_variant') ?? ($record !== null ? $record->locale_variant : '');
                                        $isDefault = $get('is_default') ?? ($record !== null ? $record->is_default : false);

                                        if (strpos($localeVariant, 'en_') === 0 && $isDefault) {
                                            return true;
                                        }

                                        return false;
                                    })
                                    ->afterStateUpdated(function ($state, $set, $livewire) {
                                        if ($state) {
                                            $set('is_active_admin', true);

                                            $currentRecordId = $livewire->record?->id;

                                            Localization::query()
                                                ->when($currentRecordId, fn ($query) => $query->where('id', '!=', $currentRecordId))
                                                ->update(['is_default' => false]);
                                        } else {
                                            $enUsLocale = Localization::where('locale_variant', 'en_US')->first();
                                            if ($enUsLocale && $enUsLocale->id !== $livewire->record?->id) {
                                                $enUsLocale->update(['is_default' => true, 'is_active_admin' => true]);
                                            }
                                        }
                                    }),
                                Toggle::make('use_native_names')
                                    ->label(__('localization::fields.use_native_names'))
                                    ->default(true),
                                Toggle::make('show_regional_variants')
                                    ->label(__('localization::fields.show_regional_variants'))
                                    ->default(true)
                                    ->live()
                                    ->afterStateUpdated(function (bool $state, Set $set): void {
                                        if (! $state) {
                                            $set('use_country_translations', false);
                                        }
                                    }),
                                Toggle::make('use_country_translations')
                                    ->label(__('localization::fields.use_country_translations'))
                                    ->default(true)
                                    ->disabled(fn (Get $get): bool => ! $get('show_regional_variants'))
                                    ->helperText(fn (Get $get): ?string => $get('show_regional_variants')
                                        ? null
                                        : __('localization::fields.country_names_requires_regional')),
                                Toggle::make('use_country_icon')
                                    ->label(__('localization::fields.use_country_icon'))
                                    ->default(false)
                                    ->helperText(__('localization::fields.use_country_icon_help')),
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
            ->checkIfRecordIsSelectableUsing(
                fn ($record): bool => $record->locale_variant !== 'en_US'
            )
            ->columns([
                // Basic Info Group
                IconColumn::make('table_flag')
                    ->label('Flag')
                    ->icon(fn (string $state): string => $state),
                TextColumn::make('display_name')
                    ->label(__('localization::fields.language'))
                    ->searchable()
                    ->width(150),
                TextColumn::make('locale_variant')
                    ->label('Locale')
                    ->width(100),
                TextColumn::make('slug')
                    ->label(__('localization::fields.slug'))
                    ->width(120),
                // Status Toggles Group
                ToggleColumn::make('is_active_admin')
                    ->label('Admin')
                    ->width(80)
                    ->disabled(fn ($record) => $record->is_default ?? false), // Disabled when set as default
                ToggleColumn::make('is_active_frontend')
                    ->label('Frontend')
                    ->width(80),
                ToggleColumn::make('is_default')
                    ->label('Default')
                    ->width(80)
                    ->disabled(function ($record) {
                        // Disabled when English is selected as default
                        $localeVariant = $record->locale_variant ?? '';
                        $isDefault = $record->is_default ?? false;

                        // If it is an English localization AND already set as default
                        if (strpos($localeVariant, 'en_') === 0 && $isDefault) {
                            return true;
                        }

                        return false;
                    })
                    ->afterStateUpdated(function ($state, $record) {
                        if ($state) {
                            // When activated as default, automatically set is_active_admin to true
                            $record->update(['is_active_admin' => true]);

                            static::getModel()::where('id', '!=', $record->id)
                                ->update(['is_default' => false]);
                        } else {
                            // When default is deactivated, always set en_US as default
                            $enUsLocale = static::getModel()::where('locale_variant', 'en_US')->first();
                            if ($enUsLocale) {
                                $enUsLocale->update(['is_default' => true, 'is_active_admin' => true]);
                            }
                        }
                    }),
                // Display name & flag toggles (order: Native → Regional → Country names → Country flag)
                ToggleColumn::make('use_native_names')
                    ->label(__('localization::fields.native'))
                    ->width(80),
                ToggleColumn::make('show_regional_variants')
                    ->label(__('localization::fields.regional'))
                    ->width(80)
                    ->afterStateUpdated(function (bool $state, Localization $record): void {
                        if (! $state) {
                            $record->update(['use_country_translations' => false]);
                        }
                    }),
                ToggleColumn::make('use_country_translations')
                    ->label(__('localization::fields.country_names'))
                    ->width(95)
                    ->tooltip(fn (Localization $record): ?string => $record->show_regional_variants
                        ? null
                        : __('localization::fields.country_names_requires_regional'))
                    ->disabled(fn (Localization $record): bool => ! $record->show_regional_variants),
                ToggleColumn::make('use_country_icon')
                    ->label(__('localization::fields.country_flag'))
                    ->width(95),
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
