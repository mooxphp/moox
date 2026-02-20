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
                                                $mainLocale = $language->alpha2.'_'.strtoupper($language->alpha2);
                                                $set('locale_variant', $mainLocale);
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
                                        $mainLocale = $baseLanguage.'_'.strtoupper($baseLanguage);

                                        $locales = StaticLocale::where('language_id', $languageId)->with('country')->get();

                                        $options = [];

                                        foreach ($locales as $locale) {
                                            $countryName = $locale->country ? $locale->country->common_name : 'Unknown';

                                            // Wenn es der Haupt-Locale ist (z.B. de_DE), zeige "Standard"
                                            if ($locale->locale === $mainLocale) {
                                                $options[$locale->locale] = $language->common_name.' (Standard)';
                                            } else {
                                                $options[$locale->locale] = $language->common_name.' ('.$countryName.')';
                                            }
                                        }

                                        // Falls kein Locale in der DB existiert, füge den Haupt-Locale hinzu
                                        if (empty($options)) {
                                            $options[$mainLocale] = $language->common_name.' (Standard)';
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
                                        // Disabled wenn diese Localization als Default gesetzt ist
                                        $isDefault = $get('is_default') ?? $livewire->record?->is_default ?? false;
                                        return $isDefault;
                                    }),
                                Toggle::make('is_active_frontend')
                                    ->label(__('localization::fields.is_activ_frontend'))
                                    ->default(false),
                                Toggle::make('is_default')
                                    ->label(__('localization::fields.is_default'))
                                    ->default(false)
                                    ->disabled(function ($get, $livewire) {
                                        // Disabled wenn Englisch als Default ausgewählt ist
                                        $localeVariant = $get('locale_variant') ?? $livewire->record?->locale_variant ?? '';
                                        $isDefault = $get('is_default') ?? $livewire->record?->is_default ?? false;

                                        // Wenn es eine englische Localization ist UND bereits als Default gesetzt ist
                                        if (strpos($localeVariant, 'en_') === 0 && $isDefault) {
                                            return true;
                                        }

                                        return false;
                                    })
                                    ->afterStateUpdated(function ($state, $set, $get, $livewire) {
                                        if ($state) {
                                            // Wenn als Default aktiviert, setze is_active_admin automatisch auf true
                                            $set('is_active_admin', true);
                                            
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
            ->checkIfRecordIsSelectableUsing(
                fn ($record): bool => $record->locale_variant !== 'en_us'
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
                    ->disabled(fn ($record) => $record->is_default ?? false), // Disabled wenn als Default gesetzt
                ToggleColumn::make('is_active_frontend')
                    ->label('Frontend')
                    ->width(80),
                ToggleColumn::make('is_default')
                    ->label('Default')
                    ->width(80)
                    ->disabled(function ($record) {
                        // Disabled wenn Englisch als Default ausgewählt ist
                        $localeVariant = $record->locale_variant ?? '';
                        $isDefault = $record->is_default ?? false;

                        // Wenn es eine englische Localization ist UND bereits als Default gesetzt ist
                        if (strpos($localeVariant, 'en_') === 0 && $isDefault) {
                            return true;
                        }

                        return false;
                    })
                    ->afterStateUpdated(function ($state, $record) {
                        if ($state) {
                            // Wenn als Default aktiviert, setze is_active_admin automatisch auf true
                            $record->update(['is_active_admin' => true]);
                            
                            static::getModel()::where('id', '!=', $record->id)
                                ->update(['is_default' => false]);
                        }
                    }),
                // Config Toggles Group
                ToggleColumn::make('language_settings->use_native_names')
                    ->label('Native')
                    ->width(80)
                    ->getStateUsing(function ($record) {
                        return $record->getLanguageSetting('use_native_names');
                    })
                    ->afterStateUpdated(function ($state, $record) {
                        $settings = $record->language_settings ?? [];
                        $settings['use_native_names'] = $state;
                        $record->update(['language_settings' => $settings]);
                    }),
                ToggleColumn::make('language_settings->show_regional_variants')
                    ->label('Regional')
                    ->width(80)
                    ->getStateUsing(function ($record) {
                        return $record->getLanguageSetting('show_regional_variants');
                    })
                    ->afterStateUpdated(function ($state, $record) {
                        $settings = $record->language_settings ?? [];
                        $settings['show_regional_variants'] = $state;
                        $record->update(['language_settings' => $settings]);
                    }),
                ToggleColumn::make('language_settings->use_country_translations')
                    ->label('Country')
                    ->width(80)
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
