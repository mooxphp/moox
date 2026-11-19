<?php

declare(strict_types=1);

namespace Moox\Product\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\HasCustomFields;
use Moox\Core\Traits\Relations\HasResourceRelations;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\Media\Tables\Columns\CustomImageColumn;
use Moox\Product\Enums\ProductType;
use Moox\Product\Models\Product;
use Moox\Product\Resources\Product\Pages\CreateProduct;
use Moox\Product\Resources\Product\Pages\EditProduct;
use Moox\Product\Resources\Product\Pages\ListProducts;
use Moox\Product\Resources\Product\Pages\ViewProduct;
use Moox\Support\Active\HasActiveForm;
use Moox\Support\Archive\HasArchiveForm;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\Data\HasDataForm;
use Moox\Support\External\HasExternalForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\HasIdentityForm;
use Moox\Support\Publish\HasPublishForm;
use Moox\Support\SoftDelete\HasSoftDeleteForm;
use Moox\Support\Title\HasTitleDescriptionForm;
use Moox\Support\Title\HasTitleForm;

class ProductResource extends Resource
{
    use HasActiveForm;
    use HasArchiveForm;
    use HasAuditForm;
    use HasCustomFields;
    use HasDataForm;
    use HasExternalForm;
    use HasIdentityForm;
    use HasPublishForm;
    use HasResourceRelations;
    use HasSoftDeleteForm;
    use HasTitleDescriptionForm;
    use HasTitleForm {
        HasTitleDescriptionForm::titleTranslationExtraFields insteadof HasTitleForm;
    }

    protected static ?string $model = Product::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-inventory-2';

    public static function getModelLabel(): string
    {
        return __('product::product.product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('product::product.products');
    }

    public static function getNavigationLabel(): string
    {
        return __('product::product.products');
    }

    public static function getNavigationGroup(): ?string
    {
        $group = config('product.navigation_group');

        return is_string($group) && $group !== '' ? $group : null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'translations',
            'canonicalProduct.translations',
            'product_category.translations',
            'parent.translations',
            'parent.product_category.translations',
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Grid::make(1)
                        ->schema(static::mainFormSections())
                        ->columnSpan(2),
                    Grid::make(1)
                        ->schema(static::metaFormSections())
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    /**
     * @return list<Section>
     */
    protected static function mainFormSections(): array
    {
        return [
            static::activeSection(),
            static::titleSection(),
            static::domainSection(),
            ...static::customFieldComponents(),
            static::mediaSection(),
            static::externalSection(),
            static::dataSection(),
        ];
    }

    /**
     * @return list<Section>
     */
    protected static function metaFormSections(): array
    {
        return [
            ...static::customFieldComponents('sidebar'),
            static::identitySection()->columns(1),
            static::auditSection()->columns(1),
            static::archiveSection()->columns(1),
            static::publishSection()->columns(1),
            static::softDeleteSection()->columns(1),
        ];
    }

    protected static function mediaSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(__($translation.'.media'))
            ->schema([
                MediaPicker::make('image')
                    ->label(__($translation.'.image'))
                    ->multiple()
                    ->columnSpanFull(),
            ]);
    }

    protected static function domainSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'domain'))
            ->schema([
                Select::make('product_category_id')
                    ->label(FeatureFields::label($translation, 'product_category_id'))
                    ->relationship(
                        name: 'product_category',
                        titleAttribute: 'title',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->with('translations'),
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn (Model $record): string => static::formatProductCategory($record)
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live()
                    ->visible(function (Get $get, string $operation): bool {
                        return $operation === 'create' || filled($get('product_category_id'));
                    })
                    ->disabled(fn (Get $get): bool => filled($get('parent_id')))
                    ->rules(static::featureRules('product_category_id')),
                Select::make('parent_id')
                    ->label(FeatureFields::label($translation, 'parent_id'))
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'title',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->with('translations')
                            ->where('type', ProductType::Grouped->value),
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn (Model $record): string => static::formatParentProduct($record)
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live()
                    ->visible(function (Get $get, string $operation): bool {
                        return $operation === 'create' || filled($get('parent_id'));
                    })
                    ->disabled(fn (Get $get): bool => filled($get('product_category_id')))
                    ->rules(static::featureRules('parent_id')),
                TextInput::make('manufacturer_id')
                    ->label(FeatureFields::label($translation, 'manufacturer_id'))
                    ->numeric()
                    ->integer()
                    ->rules(static::featureRules('manufacturer_id')),
                Select::make('type')
                    ->label(FeatureFields::label($translation, 'type'))
                    ->options(static::typeOptions())
                    ->required()
                    ->live()
                    ->rules(static::featureRules('type')),
                TextInput::make('sku')
                    ->label(FeatureFields::label($translation, 'sku'))
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => $get('type') === ProductType::Simple->value)
                    ->dehydrated(fn (Get $get): bool => $get('type') === ProductType::Simple->value)
                    ->required(fn (Get $get): bool => $get('type') === ProductType::Simple->value)
                    ->unique(ignoreRecord: true)
                    ->rules(static::featureRules('sku')),
                Toggle::make('is_orderable')
                    ->label(FeatureFields::label($translation, 'is_orderable'))
                    ->visible(fn (Get $get): bool => $get('type') === ProductType::Simple->value)
                    ->dehydrated(fn (Get $get): bool => $get('type') === ProductType::Simple->value)
                    ->rules(static::featureRules('is_orderable')),
                Toggle::make('is_canonical')
                    ->label(FeatureFields::label($translation, 'is_canonical'))
                    ->default(true)
                    ->live()
                    ->visible(fn (Get $get): bool => $get('type') === ProductType::Grouped->value)
                    ->dehydrated(fn (Get $get): bool => $get('type') === ProductType::Grouped->value)
                    ->rules(static::featureRules('is_canonical')),
                Select::make('canonical_product_id')
                    ->label(FeatureFields::label($translation, 'canonical_product_id'))
                    ->options(fn (?Model $record): array => static::canonicalOptions($record))
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => static::isGroupedReference($get))
                    ->dehydrated(fn (Get $get): bool => static::isGroupedReference($get))
                    ->required(fn (Get $get): bool => static::isGroupedReference($get))
                    ->rules(static::featureRules('canonical_product_id')),
                TextInput::make('sort_order')
                    ->label(FeatureFields::label($translation, 'sort_order'))
                    ->numeric()
                    ->integer()
                    ->default(0)
                    ->required()
                    ->rules(static::featureRules('sort_order')),
                Textarea::make('note')
                    ->label(FeatureFields::label($translation, 'note'))
                    ->rules(static::featureRules('note'))
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    protected static function formatProductCategory(Model $category): string
    {
        $title = $category->getAttribute('title');
        $reference = $category->getAttribute('external_reference');
        $title = is_string($title) ? $title : '';
        $reference = is_string($reference) ? $reference : '';

        if ($title !== '' && $reference !== '') {
            return $title.' ('.$reference.')';
        }

        return $title !== '' ? $title : $reference;
    }

    protected static function formatParentProduct(Model $product): string
    {
        $title = $product->getAttribute('title');
        $reference = $product->getAttribute('external_reference');
        $sku = $product->getAttribute('sku');
        $title = is_string($title) ? $title : '';
        $reference = is_string($reference) ? $reference : '';
        $sku = is_string($sku) ? $sku : '';

        $label = $title !== '' ? $title : ($sku !== '' ? $sku : $reference);

        if ($label !== '' && $reference !== '' && $label !== $reference) {
            return $label.' ('.$reference.')';
        }

        return $label;
    }

    protected static function featureTranslation(): string
    {
        return 'product::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return match ($field) {
            'is_active', 'is_orderable' => ['boolean'],
            'title', 'slug' => ['required', 'string', 'max:255'],
            'sku' => ['string', 'max:255'],
            'description', 'note' => ['nullable', 'string'],
            'product_category_id', 'parent_id' => ['nullable', 'integer', 'min:1'],
            'manufacturer_id', 'canonical_product_id' => ['nullable', 'integer', 'min:1'],
            'type' => ['required', 'string'],
            'is_canonical' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'external_reference', 'external_info', 'external_status' => ['nullable', 'string', 'max:255'],
            default => [],
        };
    }

    protected static function isGroupedReference(Get $get): bool
    {
        if ($get('type') !== ProductType::Grouped->value) {
            return false;
        }

        $value = $get('is_canonical');

        return $value === false || $value === 0 || $value === '0';
    }

    /**
     * @return array<string, string>
     */
    protected static function typeOptions(): array
    {
        $options = [];

        foreach (ProductType::cases() as $type) {
            $options[$type->value] = FeatureFields::label(static::featureTranslation(), 'type_'.$type->value);
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    protected static function canonicalOptions(?Model $record): array
    {
        $products = Product::query()->with('translations')->orderBy('sort_order')->get();
        $options = [];

        foreach ($products as $product) {
            $key = $product->getKey();

            if (! is_int($key) || ($record instanceof Product && $record->is($product))) {
                continue;
            }

            $title = $product->title;
            $options[$key] = is_string($title) && $title !== '' ? $title : (string) $key;
        }

        return $options;
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
                CustomImageColumn::make('image')
                ->label(FeatureFields::label($translation, 'image'))
                ->conversion('thumbnail')
                ->stacked()
                ->limit(3)
                ->limitedRemainingText()
                ->height(40),
                ...static::titleColumns(),
                TextColumn::make('sku')
                    ->label(FeatureFields::label($translation, 'sku'))
                    ->searchable()
                    ->toggleable(),
                ...static::customFieldColumns(),
                TextColumn::make('product_category.title')
                    ->label(FeatureFields::label($translation, 'product_category_id'))
                    ->placeholder('—')
                    ->toggleable()
                    ->getStateUsing(function (Model $record): ?string {
                        if (! $record instanceof Product) {
                            return null;
                        }

                        $title = $record->product_category?->title;
                        if (is_string($title) && $title !== '') {
                            return $title;
                        }

                        $parentTitle = $record->parent?->product_category?->title;

                        return is_string($parentTitle) && $parentTitle !== '' ? $parentTitle : null;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $inner) use ($search): void {
                            $inner->whereHas('product_category.translations', function (Builder $translations) use ($search): void {
                                $translations->where('title', 'like', '%'.$search.'%');
                            })->orWhereHas('parent.product_category.translations', function (Builder $translations) use ($search): void {
                                $translations->where('title', 'like', '%'.$search.'%');
                            });
                        });
                    }),
                TextColumn::make('parent.title')
                    ->label(FeatureFields::label($translation, 'parent_id'))
                    ->placeholder('—')
                    ->toggleable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('parent.translations', function (Builder $translations) use ($search): void {
                            $translations->where('title', 'like', '%'.$search.'%');
                        });
                    }),
                TextColumn::make('type')
                    ->label(FeatureFields::label($translation, 'type'))
                    ->formatStateUsing(function (mixed $state): string {
                        $value = match (true) {
                            $state instanceof ProductType => $state->value,
                            is_string($state) => $state,
                            default => '',
                        };

                        return FeatureFields::label(static::featureTranslation(), 'type_'.$value);
                    }),
                IconColumn::make('is_orderable')
                    ->label(FeatureFields::label($translation, 'is_orderable'))
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label(FeatureFields::label($translation, 'is_active'))
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label(FeatureFields::label($translation, 'sort_order'))
                    ->sortable(),
                TextColumn::make('external_reference')
                    ->label(FeatureFields::label($translation, 'external_reference'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('type')
                    ->label(FeatureFields::label($translation, 'type'))
                    ->options(static::typeOptions()),
                TernaryFilter::make('is_orderable')
                    ->label(FeatureFields::label($translation, 'is_orderable')),
                TernaryFilter::make('is_active')
                    ->label(FeatureFields::label($translation, 'is_active')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
