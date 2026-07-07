<?php

declare(strict_types=1);

namespace Moox\Product\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Moox\Core\Entities\Items\Draft\BaseDraftResource;
use Moox\Core\Traits\Relations\HasResourceRelations;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\Product\Models\Product;
use Moox\Product\Resources\Product\Pages\CreateProduct;
use Moox\Product\Resources\Product\Pages\EditProduct;
use Moox\Product\Resources\Product\Pages\ListProducts;
use Moox\Product\Resources\Product\Pages\ViewProduct;
use Moox\Slug\Forms\Components\TitleWithSlugInput;
use Override;

class ProductResource extends BaseDraftResource
{
    use HasResourceRelations;
    use HasResourceTabs;

    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-shopping-bag-o';

    protected static function getEntityType(): string
    {
        return 'product';
    }

    #[Override]
    public static function getTitleColumn(): TextColumn
    {
        return TextColumn::make('name')
            ->label(__('product::product.name'))
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
        return config('product.resources.product.single');
    }

    public static function getPluralModelLabel(): string
    {
        return config('product.resources.product.plural');
    }

    public static function getNavigationLabel(): string
    {
        return config('product.resources.product.plural');
    }

    public static function getBreadcrumb(): string
    {
        return config('product.resources.product.single');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('product.navigation_group');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->components([
                Grid::make()
                    ->schema([
                        Section::make(__('product::product.section_content'))
                            ->schema([
                                TitleWithSlugInput::make(
                                    fieldTitle: 'name',
                                    fieldSlug: 'slug',
                                    urlPathEntityType: 'products',
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
                                        'table' => 'product_translations',
                                        'column' => 'slug',
                                    ]
                                ),
                                Textarea::make('short_description')
                                    ->label(__('product::product.short_description'))
                                    ->rows(3)
                                    ->columnSpanFull(),
                                RichEditor::make('description')
                                    ->label(__('product::product.description'))
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),
                        Grid::make()
                            ->schema([
                                Section::make()
                                    ->schema([
                                        static::getFormActions(),
                                    ]),
                                Section::make(__('product::product.section_commerce'))
                                    ->schema([
                                        TextInput::make('sku')
                                            ->label(__('product::product.sku'))
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(64),
                                        Select::make('type')
                                            ->label(__('product::product.type'))
                                            ->options(config('product.types', []))
                                            ->default('simple')
                                            ->required(),
                                        TextInput::make('price')
                                            ->label(__('product::product.price'))
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->step(0.01),
                                        TextInput::make('sale_price')
                                            ->label(__('product::product.sale_price'))
                                            ->numeric()
                                            ->step(0.01),
                                        TextInput::make('cost_price')
                                            ->label(__('product::product.cost_price'))
                                            ->numeric()
                                            ->step(0.01),
                                        TextInput::make('stock')
                                            ->label(__('product::product.stock'))
                                            ->numeric()
                                            ->integer()
                                            ->default(0),
                                        TextInput::make('stock_min')
                                            ->label(__('product::product.stock_min'))
                                            ->numeric()
                                            ->integer()
                                            ->default(0),
                                        Select::make('status')
                                            ->label(__('product::product.status'))
                                            ->options(config('product.statuses', []))
                                            ->default('draft')
                                            ->required(),
                                        TextInput::make('weight')
                                            ->label(__('product::product.weight'))
                                            ->numeric()
                                            ->step(0.001),
                                        TextInput::make('weight_unit')
                                            ->label(__('product::product.weight_unit'))
                                            ->maxLength(16),
                                        TextInput::make('unit_of_measure')
                                            ->label(__('product::product.unit_of_measure'))
                                            ->maxLength(32),
                                        Toggle::make('is_purchasable')
                                            ->label(__('product::product.is_purchasable'))
                                            ->default(true),
                                        Toggle::make('is_sellable')
                                            ->label(__('product::product.is_sellable'))
                                            ->default(true),
                                    ]),
                                Section::make(__('product::product.custom_properties'))
                                    ->schema([
                                        KeyValue::make('custom_properties')
                                            ->label(__('product::product.custom_properties'))
                                            ->columnSpanFull(),
                                    ]),
                                Section::make(__('product::product.section_seo'))
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->label(__('product::product.meta_title'))
                                            ->maxLength(255),
                                        Textarea::make('meta_description')
                                            ->label(__('product::product.meta_description'))
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
                TextColumn::make('sku')
                    ->label(__('product::product.sku'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('product::product.type'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('price')
                    ->label(__('product::product.price'))
                    ->money((string) config('product.currency', 'EUR'))
                    ->sortable(),
                TextColumn::make('sale_price')
                    ->label(__('product::product.sale_price'))
                    ->money((string) config('product.currency', 'EUR'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('cost_price')
                    ->label(__('product::product.cost_price'))
                    ->money((string) config('product.currency', 'EUR'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('stock')
                    ->label(__('product::product.stock'))
                    ->sortable(),
                TextColumn::make('stock_min')
                    ->label(__('product::product.stock_min'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('product::product.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('weight')
                    ->label(__('product::product.weight'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('weight_unit')
                    ->label(__('product::product.weight_unit'))
                    ->toggleable(),
                TextColumn::make('unit_of_measure')
                    ->label(__('product::product.unit_of_measure'))
                    ->toggleable(),
                IconColumn::make('is_purchasable')
                    ->label(__('product::product.is_purchasable'))
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_sellable')
                    ->label(__('product::product.is_sellable'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('custom_properties')
                    ->label(__('product::product.custom_properties'))
                    ->formatStateUsing(fn (?array $state): string => $state ? json_encode($state, JSON_UNESCAPED_UNICODE) : '')
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
                    ->label(__('product::product.status'))
                    ->options(config('product.statuses', [])),
                SelectFilter::make('type')
                    ->label(__('product::product.type'))
                    ->options(config('product.types', [])),
            ])
            ->deferFilters(false)
            ->persistFiltersInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
            'view' => ViewProduct::route('/{record}'),
        ];
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }
}
