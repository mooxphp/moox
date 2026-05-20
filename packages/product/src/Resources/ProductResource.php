<?php

declare(strict_types=1);

namespace Moox\Product\Resources;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Moox\Core\Entities\Items\Draft\BaseDraftResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;
use Moox\Core\Traits\Taxonomy\HasResourceTaxonomy;
use Moox\Localization\Filament\Tables\Columns\TranslationColumn;
use Moox\Product\Models\Product;
use Moox\Product\Moox\Entities\Product\RelationManagers\ProductAttributeValuesRelationManager;
use Moox\Slug\Forms\Components\TitleWithSlugInput;
use Moox\Product\Resources\Product\Pages\ListProducts;
use Moox\Product\Resources\Product\Pages\CreateProduct;
use Moox\Product\Resources\Product\Pages\EditProduct;
use Moox\Product\Resources\Product\Pages\ViewProduct;

class ProductResource extends BaseDraftResource
{
    use HasResourceTabs;
    use HasResourceTaxonomy;

    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-shopping-bag-o';

    protected static function getEntityType(): string
    {
        return 'product';
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
        $taxonomyFields = static::getTaxonomyFields();

        $schema = [
            Grid::make()
                ->schema([
                    Section::make()
                        ->schema([
                            TitleWithSlugInput::make(
                                fieldTitle: 'title',
                                fieldSlug: 'slug',
                                fieldPermalink: 'permalink',
                                urlPathEntityType: 'product',
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
                            Toggle::make('is_active')
                                ->label(__('core::core.active')),
                            RichEditor::make('description')
                                ->label(__('core::core.description')),
                            MarkdownEditor::make('content')
                                ->label(__('core::core.content')),
                            Grid::make(2)
                                ->schema([
                                    static::getFooterActions()->columnSpan(1),
                                ]),
                        ])->columnSpan(2),
                    Grid::make()
                        ->schema([
                            Section::make()
                                ->schema([
                                    static::getFormActions(),
                                ]),
                            Section::make('')
                                ->schema([
                                    static::getTranslationStatusSelect(),
                                    static::getPublishDateField(),
                                    static::getUnpublishDateField(),
                                ]),
                            Section::make('')
                                ->schema($taxonomyFields),
                            Section::make('')
                                ->schema([
                                    static::getAuthorSelect(),
                                    DateTimePicker::make('due_at')
                                        ->label(__('core::core.due')),
                                    ColorPicker::make('color')
                                        ->label(__('core::core.color')),
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
        ];

        return $form
            ->components($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getTitleColumn(),
                static::getSlugColumn(),
                TranslationColumn::make('translations.locale'),
                IconColumn::make('is_active')
                    ->label(__('core::core.active'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('core::core.description'))
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('content')
                    ->label(__('core::core.content'))
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('author.name')
                    ->label(__('core::core.author'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->label(__('core::core.type'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('published_at')
                    ->label(__('core::core.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                ColorColumn::make('color')
                    ->label(__('core::core.color'))
                    ->toggleable(),
                TextColumn::make('uuid')
                    ->label(__('core::core.uuid'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ulid')
                    ->label(__('core::core.ulid'))
                    ->toggleable(isToggledHiddenByDefault: true),
                static::getStatusColumn(),
                ...static::getTaxonomyColumns(),
            ])
            ->recordActions([...static::getTableActions()])
            ->toolbarActions([...static::getBulkActions()])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('core::core.active')),
                static::getTranslationStatusFilter(),
                SelectFilter::make('type')
                    ->label(__('core::core.type'))
                    ->options(['simple' => 'Simple', 'bundle' => 'Bundle']),
                ...static::getTaxonomyFilters(),
            ])->deferFilters(false)
            ->persistFiltersInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' =>  ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
            'view' => ViewProduct::route('/{record}'),
        ];
    }

    /**
     * @return array<class-string>
     */
    public static function getRelations(): array
    {
        return [
            ProductAttributeValuesRelationManager::class,
        ];
    }

    public static function setCurrentTab(?string $tab): void
    {
        static::$currentTab = $tab;
    }
}
