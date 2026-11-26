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
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\HasCustomFields;
use Moox\Core\Traits\Relations\HasResourceRelations;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\Product\Enums\ProductType;
use Moox\Product\Models\Product;
use Moox\Product\Models\Variant;
use Moox\Product\Resources\Variant\Pages\CreateVariant;
use Moox\Product\Resources\Variant\Pages\EditVariant;
use Moox\Product\Resources\Variant\Pages\ListVariants;
use Moox\Product\Resources\Variant\Pages\ViewVariant;
use Moox\Support\Active\Columns\HasActiveColumns;
use Moox\Support\Active\Forms\HasActiveForm;
use Moox\Support\Archive\Forms\HasArchiveForm;
use Moox\Support\Audit\Forms\HasAuditForm;
use Moox\Support\Data\Forms\HasDataForm;
use Moox\Support\External\Forms\HasExternalForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\Forms\HasIdentityForm;
use Moox\Support\Publish\Forms\HasPublishForm;
use Moox\Support\SoftDelete\Forms\HasSoftDeleteForm;
use Moox\Support\Title\Forms\HasOptionalTitleSlugForm;
use Moox\Support\Title\Forms\HasTitleDescriptionForm;
use Moox\Support\Title\Forms\HasTitleForm;

class VariantResource extends Resource
{
    use HasActiveColumns;
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
    use HasTitleForm;
    use HasTitleDescriptionForm;
    use HasOptionalTitleSlugForm;

    protected static ?string $model = Variant::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-layers';

    public static function getModelLabel(): string
    {
        return __('product::variant.variant');
    }

    public static function getPluralModelLabel(): string
    {
        return __('product::variant.variants');
    }

    public static function getNavigationLabel(): string
    {
        return __('product::variant.variants');
    }

    public static function getNavigationGroup(): ?string
    {
        $group = config('product.navigation_group');

        return is_string($group) && $group !== '' ? $group : null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['translations', 'product.translations']);
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

        return Section::make(FeatureFields::label($translation, 'variant_domain'))
            ->schema([
                Select::make('product_id')
                    ->label(FeatureFields::label($translation, 'product_id'))
                    ->searchable()
                    ->required()
                    ->getSearchResultsUsing(fn (string $search): array => static::simpleProductOptions($search))
                    ->getOptionLabelUsing(fn (mixed $value): ?string => static::simpleProductLabel($value))
                    ->rules(static::featureRules('product_id')),
                TextInput::make('manufacturer_id')
                    ->label(FeatureFields::label($translation, 'manufacturer_id'))
                    ->numeric()
                    ->integer()
                    ->rules(static::featureRules('manufacturer_id')),
                TextInput::make('manufacturer_name')
                    ->label(FeatureFields::label($translation, 'manufacturer_name'))
                    ->maxLength(255)
                    ->rules(static::featureRules('manufacturer_name')),
                TextInput::make('sku')
                    ->label(FeatureFields::label($translation, 'sku'))
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rules(static::featureRules('sku')),
                Toggle::make('is_orderable')
                    ->label(FeatureFields::label($translation, 'is_orderable'))
                    ->default(false)
                    ->rules(static::featureRules('is_orderable')),
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

    /**
     * @return array<int|string, string>
     */
    protected static function simpleProductOptions(string $search): array
    {
        return Product::query()
            ->with('translations')
            ->where('type', ProductType::Simple->value)
            ->where(function (Builder $query) use ($search): void {
                $query->where('sku', 'like', '%'.$search.'%')
                    ->orWhere('external_reference', 'like', '%'.$search.'%')
                    ->orWhereHas('translations', function (Builder $translations) use ($search): void {
                        $translations->where('title', 'like', '%'.$search.'%');
                    });
            })
            ->orderBy('external_reference')
            ->limit(50)
            ->get()
            ->mapWithKeys(function (Product $product): array {
                $key = $product->getKey();

                if (! is_int($key) && ! is_string($key)) {
                    return [];
                }

                return [$key => static::formatProduct($product)];
            })
            ->all();
    }

    protected static function simpleProductLabel(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $product = Product::query()->with('translations')->find($value);

        return $product instanceof Product ? static::formatProduct($product) : null;
    }

    protected static function formatProduct(Product $product): string
    {
        $title = $product->title ?? '';
        $sku = $product->sku ?? '';

        if ($title !== '' && $sku !== '') {
            return $title.' ('.$sku.')';
        }

        return $title !== '' ? $title : $sku;
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
            'title', 'slug', 'manufacturer_name' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'description', 'note' => ['nullable', 'string'],
            'product_id' => ['required', 'integer', 'min:1'],
            'manufacturer_id' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'external_reference', 'external_info', 'external_status' => ['nullable', 'string', 'max:255'],
            default => [],
        };
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();

        return $table
            ->columns([
                ...static::titleColumns(),
                TextColumn::make('product_id')
                    ->label(FeatureFields::label($translation, 'product_id'))
                    ->formatStateUsing(function (mixed $state, Model $record): string {
                        if (! $record instanceof Variant) {
                            return '';
                        }

                        $product = $record->product;

                        if ($product instanceof Product) {
                            return static::formatProduct($product);
                        }

                        if (is_int($state) || is_string($state)) {
                            return (string) $state;
                        }

                        return '';
                    }),
                TextColumn::make('sku')
                    ->label(FeatureFields::label($translation, 'sku'))
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_orderable')
                    ->label(FeatureFields::label($translation, 'is_orderable'))
                    ->boolean(),
                static::activeColumn(),
                TextColumn::make('sort_order')
                    ->label(FeatureFields::label($translation, 'sort_order'))
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_orderable')
                    ->label(FeatureFields::label($translation, 'is_orderable')),
                static::activeFilter(),
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
            'index' => ListVariants::route('/'),
            'create' => CreateVariant::route('/create'),
            'view' => ViewVariant::route('/{record}'),
            'edit' => EditVariant::route('/{record}/edit'),
        ];
    }
}
