<?php

declare(strict_types=1);

namespace Moox\ProductCategory\Resources;

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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\Core\Traits\Relations\HasResourceRelations;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\Media\Tables\Columns\CustomImageColumn;
use Moox\ProductCategory\Models\ProductCategory;
use Moox\ProductCategory\Resources\ProductCategory\Pages\CreateProductCategory;
use Moox\ProductCategory\Resources\ProductCategory\Pages\EditProductCategory;
use Moox\ProductCategory\Resources\ProductCategory\Pages\ListProductCategories;
use Moox\ProductCategory\Resources\ProductCategory\Pages\ViewProductCategory;
use Moox\Support\Active\Columns\HasActiveColumns;
use Moox\Support\Active\Forms\HasActiveForm;
use Moox\Support\Archive\Forms\HasArchiveForm;
use Moox\Support\Audit\Forms\HasAuditForm;
use Moox\Support\Data\Forms\HasDataForm;
use Moox\Support\External\Columns\HasExternalColumns;
use Moox\Support\External\Forms\HasExternalForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\Forms\HasIdentityForm;
use Moox\Support\Publish\Forms\HasPublishForm;
use Moox\Support\SoftDelete\Forms\HasSoftDeleteForm;
use Moox\Support\Title\Forms\HasTitleDescriptionForm;
use Moox\Support\Title\Forms\HasTitleForm;

class ProductCategoryResource extends Resource
{
    use HasActiveColumns;
    use HasActiveForm;
    use HasArchiveForm;
    use HasAuditForm;
    use HasDataForm;
    use HasExternalColumns;
    use HasExternalForm;
    use HasIdentityForm;
    use HasPublishForm;
    use HasResourceRelations;
    use HasSoftDeleteForm;
    use HasTitleForm;
    use HasTitleDescriptionForm;

    protected static ?string $model = ProductCategory::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-shopping-bag-o';

    public static function getModelLabel(): string
    {
        return __('productcategory::productcategory.productcategory');
    }

    public static function getPluralModelLabel(): string
    {
        return __('productcategory::productcategory.productcategories');
    }

    public static function getNavigationLabel(): string
    {
        return __('productcategory::productcategory.productcategories');
    }

    public static function getNavigationGroup(): ?string
    {
        $group = config('productcategory.navigation_group');

        return is_string($group) && $group !== '' ? $group : null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['translations', 'parent.translations']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Grid::make(1)
                        ->schema([
                            static::activeSection(),
                            static::titleSection(),
                            static::domainSection(),
                            static::mediaSection(),
                            static::externalSection(),
                            static::dataSection(),
                        ])
                        ->columnSpan(2),
                    Grid::make(1)
                        ->schema([
                            static::identitySection()->columns(1),
                            static::auditSection()->columns(1),
                            static::archiveSection()->columns(1),
                            static::publishSection()->columns(1),
                            static::softDeleteSection()->columns(1),
                        ])
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
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
                Select::make('parent_id')
                    ->label(FeatureFields::label($translation, 'parent_id'))
                    ->options(fn (?Model $record): array => static::parentOptions($record))
                    ->searchable()
                    ->preload()
                    ->rules(static::featureRules('parent_id')),
                Toggle::make('is_canonical')
                    ->label(FeatureFields::label($translation, 'is_canonical'))
                    ->default(false)
                    ->live()
                    ->rules(static::featureRules('is_canonical')),
                Select::make('canonical_product_category_id')
                    ->label(FeatureFields::label($translation, 'canonical_product_category_id'))
                    ->options(fn (?Model $record): array => static::canonicalOptions($record))
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => static::isReference($get))
                    ->dehydrated(fn (Get $get): bool => static::isReference($get))
                    ->required(fn (Get $get): bool => static::isReference($get))
                    ->rules(static::featureRules('canonical_product_category_id')),
                TextInput::make('sort_order')
                    ->label(FeatureFields::label($translation, 'sort_order'))
                    ->numeric()
                    ->integer()
                    ->default(0)
                    ->required()
                    ->rules(static::featureRules('sort_order')),
                TextInput::make('lft')
                    ->label(FeatureFields::label($translation, 'lft'))
                    ->numeric()
                    ->integer()
                    ->default(0)
                    ->rules(static::featureRules('lft')),
                TextInput::make('rgt')
                    ->label(FeatureFields::label($translation, 'rgt'))
                    ->numeric()
                    ->integer()
                    ->default(0)
                    ->rules(static::featureRules('rgt')),
                Textarea::make('note')
                    ->label(FeatureFields::label($translation, 'note'))
                    ->rules(static::featureRules('note'))
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    protected static function featureTranslation(): string
    {
        return 'productcategory::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return match ($field) {
            'is_active' => ['boolean'],
            'title', 'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer'],
            'is_canonical' => ['boolean'],
            'canonical_product_category_id' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'lft', 'rgt' => ['nullable', 'integer', 'min:0'],
            'external_reference', 'external_info', 'external_status' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            default => [],
        };
    }

    /**
     * @return array<int, string>
     */
    protected static function parentOptions(?Model $record): array
    {
        /** @var Collection<int, ProductCategory> $groups */
        $groups = ProductCategory::query()->with('translations')->orderBy('sort_order')->get();
        $excluded = [];

        if ($record instanceof ProductCategory) {
            $recordKey = static::integerKey($record);

            if ($recordKey !== null) {
                $excluded = [$recordKey, ...static::descendantIds($groups, $recordKey)];
            }
        }

        $options = [];

        foreach ($groups as $group) {
            $key = static::integerKey($group);

            if ($key === null || in_array($key, $excluded, true)) {
                continue;
            }

            $title = $group->title;
            $options[$key] = is_string($title) && $title !== '' ? $title : (string) $key;
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    protected static function canonicalOptions(?Model $record): array
    {
        /** @var Collection<int, ProductCategory> $groups */
        $groups = ProductCategory::query()->with('translations')->orderBy('sort_order')->get();
        $options = [];

        foreach ($groups as $group) {
            $key = static::integerKey($group);

            if ($key === null || ($record instanceof ProductCategory && $record->is($group))) {
                continue;
            }

            $title = $group->title;
            $options[$key] = is_string($title) && $title !== '' ? $title : (string) $key;
        }

        return $options;
    }

    protected static function isReference(Get $get): bool
    {
        $value = $get('is_canonical');

        return $value === true || $value === 1 || $value === '1';
    }

    /**
     * @param  Collection<int, ProductCategory>  $groups
     * @return list<int>
     */
    protected static function descendantIds(Collection $groups, int $id): array
    {
        /** @var array<int, list<int>> $childrenByParent */
        $childrenByParent = [];

        foreach ($groups as $group) {
            $key = static::integerKey($group);

            if ($group->parent_id === null || $key === null) {
                continue;
            }

            $childrenByParent[$group->parent_id][] = $key;
        }

        $ids = [];
        $pending = $childrenByParent[$id] ?? [];

        for ($index = 0; $index < count($pending); $index++) {
            $current = $pending[$index];

            if (in_array($current, $ids, true)) {
                continue;
            }

            $ids[] = $current;

            foreach ($childrenByParent[$current] ?? [] as $childId) {
                $pending[] = $childId;
            }
        }

        return $ids;
    }

    protected static function integerKey(Model $model): ?int
    {
        $key = $model->getKey();

        return is_int($key) ? $key : null;
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
                TextColumn::make('parent_id')
                    ->label(FeatureFields::label($translation, 'parent_id'))
                    ->formatStateUsing(function (mixed $state, Model $record): string {
                        if (! $record instanceof ProductCategory || ! $record->parent instanceof ProductCategory) {
                            return '';
                        }

                        $title = $record->parent->title;
                        $key = static::integerKey($record->parent);

                        return is_string($title) && $title !== '' ? $title : ($key === null ? '' : (string) $key);
                    })
                    ->toggleable(),
                TextColumn::make('sort_order')
                    ->label(FeatureFields::label($translation, 'sort_order'))
                    ->sortable(),
                static::activeColumn(),
                static::externalReferenceColumn(),
            ])
            ->defaultSort('sort_order')
            ->filters([
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
            'index' => ListProductCategories::route('/'),
            'create' => CreateProductCategory::route('/create'),
            'view' => ViewProductCategory::route('/{record}'),
            'edit' => EditProductCategory::route('/{record}/edit'),
        ];
    }
}
