<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Support\Collection;
use Moox\Core\Traits\Relations\HasResourceRelations;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\ProductGroup\Models\ProductGroup;
use Moox\ProductGroup\Resources\ProductGroup\Pages\CreateProductGroup;
use Moox\ProductGroup\Resources\ProductGroup\Pages\EditProductGroup;
use Moox\ProductGroup\Resources\ProductGroup\Pages\ListProductGroups;
use Moox\ProductGroup\Resources\ProductGroup\Pages\ViewProductGroup;
use Moox\Support\Active\HasActiveForm;
use Moox\Support\Archive\HasArchiveForm;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\Data\HasDataForm;
use Moox\Support\External\HasExternalForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\HasIdentityForm;
use Moox\Support\SoftDelete\HasSoftDeleteForm;
use Moox\Support\Title\HasTitleForm;

class ProductGroupResource extends Resource
{
    use HasActiveForm;
    use HasArchiveForm;
    use HasAuditForm;
    use HasDataForm;
    use HasExternalForm;
    use HasIdentityForm;
    use HasResourceRelations;
    use HasSoftDeleteForm;
    use HasTitleForm;

    protected static ?string $model = ProductGroup::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-shopping-bag-o';

    public static function getModelLabel(): string
    {
        return __('productgroup::productgroup.productgroup');
    }

    public static function getPluralModelLabel(): string
    {
        return __('productgroup::productgroup.productgroups');
    }

    public static function getNavigationLabel(): string
    {
        return __('productgroup::productgroup.productgroups');
    }

    public static function getNavigationGroup(): ?string
    {
        $group = config('productgroup.navigation_group');

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

    protected static function mediaSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'media'))
            ->schema([
                MediaPicker::make('image')
                    ->label(FeatureFields::label($translation, 'image'))
                    ->multiple()
                    ->columnSpanFull(),
            ]);
    }

    protected static function publishSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'publish'))
            ->schema([
                FeatureFields::locked($translation, 'published_at'),
                FeatureFields::locked($translation, 'published_by_id'),
                FeatureFields::locked($translation, 'published_by_type'),
                FeatureFields::locked($translation, 'unpublished_at'),
                FeatureFields::locked($translation, 'unpublished_by_id'),
                FeatureFields::locked($translation, 'unpublished_by_type'),
            ])
            ->columns(2)
            ->hidden(fn (?Model $record): bool => $record === null);
    }

    protected static function titleSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'titles'))
            ->schema([
                Repeater::make('translations')
                    ->label(FeatureFields::label($translation, 'translations'))
                    ->relationship()
                    ->schema([
                        Select::make('locale')
                            ->label(FeatureFields::label($translation, 'locale'))
                            ->options(static::titleLocaleOptions())
                            ->required()
                            ->searchable()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                        ...static::titleSlugFields(),
                        Textarea::make('description')
                            ->label(FeatureFields::label($translation, 'description'))
                            ->rows(4)
                            ->rules(static::featureRules('description'))
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->minItems(1)
                    ->columnSpanFull(),
            ]);
    }

    protected static function featureTranslation(): string
    {
        return 'productgroup::fields';
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
        /** @var Collection<int, ProductGroup> $groups */
        $groups = ProductGroup::query()->with('translations')->orderBy('sort_order')->get();
        $excluded = [];

        if ($record instanceof ProductGroup) {
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
     * @param  Collection<int, ProductGroup>  $groups
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
                ...static::titleColumns(),
                TextColumn::make('parent_id')
                    ->label(FeatureFields::label($translation, 'parent_id'))
                    ->formatStateUsing(function (mixed $state, Model $record): string {
                        if (! $record instanceof ProductGroup || ! $record->parent instanceof ProductGroup) {
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
                IconColumn::make('is_active')
                    ->label(FeatureFields::label($translation, 'is_active'))
                    ->boolean(),
                TextColumn::make('external_reference')
                    ->label(FeatureFields::label($translation, 'external_reference'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
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
            'index' => ListProductGroups::route('/'),
            'create' => CreateProductGroup::route('/create'),
            'view' => ViewProductGroup::route('/{record}'),
            'edit' => EditProductGroup::route('/{record}/edit'),
        ];
    }
}
