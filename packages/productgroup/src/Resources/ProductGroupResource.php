<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Moox\Core\Entities\Items\Draft\BaseDraftResource;
use Moox\Core\Traits\HasCustomFields;
use Moox\Core\Traits\Relations\HasResourceRelations;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\ProductGroup\Models\ProductGroup;
use Moox\ProductGroup\Resources\ProductGroup\Pages\CreateProductGroup;
use Moox\ProductGroup\Resources\ProductGroup\Pages\EditProductGroup;
use Moox\ProductGroup\Resources\ProductGroup\Pages\ListProductGroups;
use Moox\ProductGroup\Resources\ProductGroup\Pages\ViewProductGroup;
use Moox\Slug\Forms\Components\TitleWithSlugInput;
use Override;

class ProductGroupResource extends BaseDraftResource
{
    use HasCustomFields;
    use HasResourceRelations;
    use HasResourceTabs;

    protected static ?string $model = ProductGroup::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-shopping-bag-o';

    protected static function getEntityType(): string
    {
        return 'productgroup';
    }

    #[Override]
    public static function getTitleColumn(): TextColumn
    {
        return TextColumn::make('name')
            ->label(__('productgroup::productgroup.name'))
            ->searchable(true, function ($query, $search, $livewire): void {
                $currentLang = static::resolveCurrentLang($livewire);
                $query->whereHas('translations', function ($query) use ($search, $currentLang): void {
                    $query->where('locale', $currentLang)
                        ->where('name', 'like', '%'.$search.'%');
                });
            })
            ->extraAttributes(function ($record, $livewire): array {
                $currentLang = static::resolveCurrentLang($livewire);

                return [
                    'style' => $record->translations()->where('locale', $currentLang)->withTrashed()->whereNotNull('name')->exists()
                        ? ''
                        : 'color: var(--gray-500);',
                ];
            })
            ->getStateUsing(function ($record, $livewire) {
                $currentLang = static::resolveCurrentLang($livewire);
                $translation = $record->translations()->withTrashed()->where('locale', $currentLang)->first();

                if ($translation?->name) {
                    return $translation->name;
                }

                return __('core::core.no_title_available');
            });
    }

    public static function getModelLabel(): string
    {
        return config('productgroup.resources.productgroup.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('productgroup.resources.productgroup.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('productgroup.resources.productgroup.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('productgroup.resources.productgroup.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('productgroup.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->components([
                Grid::make()
                    ->schema([
                        Section::make(__('productgroup::productgroup.section_content'))
                            ->schema([
                                TitleWithSlugInput::make(
                                    fieldTitle: 'name',
                                    fieldSlug: 'slug',
                                    urlPathEntityType: 'productgroups',
                                    slugRuleUniqueParameters: [
                                        'modifyRuleUsing' => function (Unique $rule, $record, $livewire) {
                                            $locale = $livewire->lang;
                                            if ($record) {
                                                $rule->where('locale', $locale);
                                                $existingTranslation = $record->translations()
                                                    ->where('locale', $locale)
                                                    ->first();
                                                if ($existingTranslation) {
                                                    $rule->ignore($existingTranslation->id);
                                                }
                                            } else {
                                                $rule->where('locale', $locale);
                                            }

                                            return $rule;
                                        },
                                        'table' => 'productgroup_translations',
                                        'column' => 'slug',
                                    ]
                                ),
                                Textarea::make('short_description')
                                    ->label(__('productgroup::productgroup.short_description'))
                                    ->rows(3)
                                    ->columnSpanFull(),
                                RichEditor::make('description')
                                    ->label(__('productgroup::productgroup.description'))
                                    ->columnSpanFull(),
                                ...static::customFieldComponents(),
                                KeyValue::make('custom_properties')
                                    ->label(__('productgroup::productgroup.custom_properties'))
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),
                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        static::getFormActions(),
                                    ]),
                                Section::make(__('productgroup::productgroup.section_family'))
                                    ->schema([
                                        TextInput::make('code')
                                            ->label(__('productgroup::productgroup.code'))
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(64),
                                        Select::make('type')
                                            ->label(__('productgroup::productgroup.type'))
                                            ->options(config('productgroup.types', []))
                                            ->default('family')
                                            ->required(),
                                        Select::make('status')
                                            ->label(__('productgroup::productgroup.status'))
                                            ->options(config('productgroup.statuses', []))
                                            ->default('draft')
                                            ->required(),
                                        Select::make('parent_id')
                                            ->label(__('productgroup::productgroup.parent'))
                                            ->relationship('parent', 'code')
                                            ->searchable()
                                            ->preload(),
                                        TextInput::make('sku_prefix')
                                            ->label(__('productgroup::productgroup.sku_prefix'))
                                            ->maxLength(64),
                                    ]),
                                ...static::customFieldComponents('sidebar'),

                                Section::make(__('productgroup::productgroup.section_seo'))
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->label(__('productgroup::productgroup.meta_title'))
                                            ->maxLength(255),
                                        Textarea::make('meta_description')
                                            ->label(__('productgroup::productgroup.meta_description'))
                                            ->rows(3),
                                    ]),
                                Section::make()
                                    ->schema([
                                        static::getTranslationStatusSelect(),
                                        static::getPublishDateField(),
                                        static::getUnpublishDateField(),
                                    ]),
                                Section::make('')
                                    ->schema([
                                        ...static::getStandardCopyableFields(),
                                        Section::make('')
                                            ->schema([
                                                ...static::getStandardTimestampFields(),
                                            ]),
                                    ])
                                    ->hidden(fn ($record) => $record === null),
                            ])
                            ->columnSpan(1)
                            ->columns(1),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getTitleColumn(),
                static::getSlugColumn(),
                TranslationColumn::make('translations.locale'),
                TextColumn::make('code')
                    ->label(__('productgroup::productgroup.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('productgroup::productgroup.type'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('productgroup::productgroup.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('parent.name')
                    ->label(__('productgroup::productgroup.parent'))
                    ->toggleable(),
                TextColumn::make('sku_prefix')
                    ->label(__('productgroup::productgroup.sku_prefix'))
                    ->toggleable(),
                ...static::customFieldColumns(),
                TextColumn::make('custom_properties')
                    ->label(__('productgroup::productgroup.custom_properties'))
                    ->formatStateUsing(function (mixed $state): string {
                        if ($state === null || $state === []) {
                            return '';
                        }

                        if (is_array($state)) {
                            return json_encode($state, JSON_UNESCAPED_UNICODE) ?: '';
                        }

                        return is_scalar($state) ? (string) $state : (json_encode($state, JSON_UNESCAPED_UNICODE) ?: '');
                    })
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ulid')
                    ->label('ULID')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                static::getStatusColumn(),
            ])
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                static::getTranslationStatusFilter(),
                SelectFilter::make('status')
                    ->label(__('productgroup::productgroup.status'))
                    ->options(config('productgroup.statuses', [])),
                SelectFilter::make('type')
                    ->label(__('productgroup::productgroup.type'))
                    ->options(config('productgroup.types', [])),
                ...static::customFieldFilters(),
            ])
            ->deferFilters(false)
            ->persistFiltersInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductGroups::route('/'),
            'create' => CreateProductGroup::route('/create'),
            'edit' => EditProductGroup::route('/{record}/edit'),
            'view' => ViewProductGroup::route('/{record}'),
        ];
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }
}
