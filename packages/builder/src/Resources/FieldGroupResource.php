<?php

declare(strict_types=1);

namespace Moox\Builder\Resources;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Moox\Builder\Exceptions\UnknownFieldTypeException;
use Moox\Builder\Filament\Actions\FieldGroupDefinitionActions;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Resources\FieldGroupResource\Pages\CreateFieldGroup;
use Moox\Builder\Resources\FieldGroupResource\Pages\EditFieldGroup;
use Moox\Builder\Resources\FieldGroupResource\Pages\ListFieldGroups;
use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Support\BuilderLocaleResolver;
use Moox\Builder\Support\ConditionalLogic;
use Moox\Builder\Support\FieldGroupPlacement;
use Moox\Builder\Support\FieldValidationRules;
use Moox\Builder\Support\FieldWidth;
use Moox\Builder\Support\LocationConstraintOptions;
use Moox\Builder\Support\TypedValueColumns;

class FieldGroupResource extends Resource
{
    /**
     * @var array<string, string>
     */
    protected static array $fieldTypeIconSvgCache = [];

    /** @var array<string, list<class-string>> */
    protected static array $typeSettingsCapabilityCache = [];

    protected static ?string $model = FieldGroup::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        $group = config('builder.navigation_group');

        return filled($group) ? (string) $group : __('builder::builder.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('builder::builder.field_group.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('builder::builder.field_group.plural');
    }

    public static function form(Schema $schema): Schema
    {
        $registry = app(FieldTypeRegistry::class);
        $entityOptions = app(EntityRegistry::class)->optionsForSelect();

        return $schema->components([
            Grid::make()
                ->schema([
                    Grid::make()
                        ->schema([
                            Section::make(__('builder::builder.field_group.general'))
                                ->schema([
                                    TextInput::make('name')
                                        ->label(__('builder::builder.field_group.name'))
                                        ->helperText(__('builder::builder.field_group.name_helper'))
                                        ->required()
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set, ?FieldGroup $record): void {
                                            if ($record !== null) {
                                                return;
                                            }

                                            $set('slug', Str::slug((string) $state));
                                        }),
                                    TextInput::make('slug')
                                        ->label(__('builder::builder.field_group.slug'))
                                        ->helperText(__('builder::builder.field_group.slug_helper'))
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(ignoreRecord: true)
                                        ->alphaDash(),
                                    Toggle::make('active')
                                        ->label(__('builder::builder.field_group.active'))
                                        ->helperText(__('builder::builder.field_group.active_helper'))
                                        ->default(true)
                                        ->inline(false),
                                    TextInput::make('sort')
                                        ->label(__('builder::builder.field_group.sort'))
                                        ->helperText(__('builder::builder.field_group.sort_helper'))
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0),
                                    Select::make('placement')
                                        ->label(__('builder::builder.field_group.placement'))
                                        ->helperText(__('builder::builder.field_group.placement_helper'))
                                        ->options(static::placementOptions())
                                        ->default(FieldGroupPlacement::MAIN)
                                        ->selectablePlaceholder(false)
                                        ->native(false),
                                    Select::make('settings.columns')
                                        ->label(__('builder::builder.field_group.columns'))
                                        ->helperText(__('builder::builder.field_group.columns_helper'))
                                        ->options(static::columnsOptions())
                                        ->default(1)
                                        ->selectablePlaceholder(false)
                                        ->native(false),
                                ]),
                            Section::make(__('builder::builder.field_group.assignment'))
                                ->schema([
                                    Select::make('target_entities')
                                        ->label(__('builder::builder.field_group.target_entities'))
                                        ->helperText(
                                            $entityOptions === []
                                                ? __('builder::builder.field_group.no_entities_registered')
                                                : __('builder::builder.field_group.target_entities_helper'),
                                        )
                                        ->options($entityOptions)
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->placeholder(__('builder::builder.field_group.target_entities_placeholder'))
                                        ->disabled($entityOptions === [])
                                        ->live()
                                        ->afterStateUpdated(function (mixed $state, callable $set, callable $get): void {
                                            $set(
                                                'location_constraints',
                                                static::sanitizeLocationConstraintsForEntities(
                                                    is_array($get('location_constraints')) ? $get('location_constraints') : [],
                                                    $state,
                                                ),
                                            );
                                        })
                                        ->native(false),
                                    Repeater::make('location_constraints')
                                        ->label(__('builder::builder.field_group.location_constraints'))
                                        ->helperText(fn (Get $get): string => static::locationConstraintsHelperText($get('target_entities')))
                                        ->default([])
                                        ->addActionLabel(__('builder::builder.field_group.location_constraints_add'))
                                        ->itemLabel(fn (array $state, Get $get): string => static::locationConstraintItemLabel($state, $get('../../target_entities')))
                                        ->schema(static::locationConstraintSchema())
                                        ->columns(1)
                                        ->collapsible()
                                        ->collapsed(),
                                ]),
                            Section::make(__('builder::builder.field_group.visibility'))
                                ->description(__('builder::builder.field_group.visibility_helper'))
                                ->collapsible()
                                ->collapsed()
                                ->schema(static::visibilityToggles()),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                    Section::make(__('builder::builder.field_group.fields'))
                        ->columnSpan(2)
                        ->headerActions(static::fieldRepeaterHeaderActions())
                        ->schema([
                            Repeater::make('fields')
                                ->hiddenLabel()
                                ->extraAttributes(['class' => 'moox-builder-fields'])
                                ->orderColumn('sort')
                                ->reorderable()
                                ->collapsible()
                                ->collapsed()
                                ->cloneable()
                                ->collapseAllAction(fn (Action $action): Action => $action->hidden())
                                ->expandAllAction(fn (Action $action): Action => $action->hidden())
                                ->itemLabel(fn (array $state): HtmlString => static::fieldRepeaterItemLabel($registry, $state))
                                ->schema([
                                    Hidden::make('id'),
                                    Hidden::make('sort'),
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('label')
                                                ->label(__('builder::builder.field.label'))
                                                ->helperText(__('builder::builder.field.label_helper'))
                                                ->required()
                                                ->maxLength(255)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                                    if (blank($get('name'))) {
                                                        $set('name', Str::slug((string) $state, '-'));
                                                    }
                                                }),
                                            Select::make('type')
                                                ->label(__('builder::builder.field.type'))
                                                ->options($registry->optionsForSelect())
                                                ->required()
                                                ->searchable()
                                                ->live()
                                                ->afterStateUpdated(fn ($state, callable $set, callable $get): mixed => static::seedConfigForFieldType((string) $state, $set, $get))
                                                ->native(false),
                                        ]),
                                    TextInput::make('name')
                                        ->label(__('builder::builder.field.name'))
                                        ->helperText(__('builder::builder.field.name_helper'))
                                        ->required()
                                        ->maxLength(255)
                                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                                        ->live(onBlur: true),
                                    static::requirementAndWidthRow(),
                                    ...static::validationSettingsSchema(),
                                    ...static::columnSettingsSchema(),
                                    ...static::filterSettingsSchema(),
                                    ...static::visibilitySettingsSchema(),
                                    ...static::conditionalLogicSchema(),
                                    ...static::optionFieldSections($registry),
                                    Section::make(fn (callable $get): string => $get('type') === 'tab'
                                        ? __('builder::builder.field.tab_content')
                                        : __('builder::builder.field.subfields'))
                                        ->description(fn (callable $get): string => $get('type') === 'tab'
                                            ? __('builder::builder.field.tab_content_helper')
                                            : __('builder::builder.field.subfields_helper'))
                                        ->icon(fn (callable $get): Heroicon => $get('type') === 'tab'
                                            ? Heroicon::OutlinedFolder
                                            : Heroicon::OutlinedSquares2x2)
                                        ->collapsed()
                                        ->schema([
                                            Repeater::make('children')
                                                ->hiddenLabel()
                                                ->orderColumn('sort')
                                                ->reorderable()
                                                ->collapsible()
                                                ->collapsed()
                                                ->itemLabel(fn (array $state): HtmlString => static::fieldRepeaterItemLabel($registry, $state))
                                                ->schema(static::tabChildFieldSchema($registry))
                                                ->defaultItems(0),
                                        ])
                                        ->visible(fn (callable $get): bool => filled($get('type')) && $registry->get($get('type'))->hasSubFields() && $get('type') !== 'flexible_content'),
                                    Section::make(__('builder::builder.field.layouts'))
                                        ->description(__('builder::builder.field.layouts_helper'))
                                        ->icon(Heroicon::OutlinedSquaresPlus)
                                        ->collapsed()
                                        ->schema([
                                            Repeater::make('layouts')
                                                ->hiddenLabel()
                                                ->orderColumn('sort')
                                                ->reorderable()
                                                ->collapsible()
                                                ->collapsed()
                                                ->itemLabel(fn (array $state): string => static::layoutRepeaterItemLabel($registry, $state))
                                                ->schema(static::layoutSchema($registry))
                                                ->defaultItems(0),
                                        ])
                                        ->visible(fn (callable $get): bool => filled($get('type')) && $get('type') === 'flexible_content'),
                                ]),
                        ]),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected static function fieldRepeaterItemLabel(FieldTypeRegistry $registry, array $state): HtmlString
    {
        $type = filled($state['type'] ?? null) ? (string) $state['type'] : null;

        $title = filled($state['label'] ?? null)
            ? (string) $state['label']
            : __('builder::builder.field_group.field_item');

        $meta = [];

        if ($type !== null) {
            $meta[] = static::fieldTypeLabel($registry, $type);
        }

        if (filled($state['name'] ?? null)) {
            $meta[] = (string) $state['name'];
        }

        if (($state['required'] ?? false) === true) {
            $meta[] = __('builder::builder.field.required_badge');
        }

        $childrenCount = count($state['children'] ?? []);
        if ($childrenCount > 0) {
            $meta[] = trans_choice('builder::builder.field.subfields_count', $childrenCount, [
                'count' => $childrenCount,
            ]);
        }

        $layoutsCount = count($state['layouts'] ?? []);
        if ($layoutsCount > 0) {
            $meta[] = trans_choice('builder::builder.field.layouts_count', $layoutsCount, [
                'count' => $layoutsCount,
            ]);
        }

        return static::fieldItemLabelHtml($registry, $type, $title, $meta);
    }

    /**
     * Renders the repeater item header as an icon badge + title + muted meta,
     * giving each field a strong visual anchor when several are expanded.
     *
     * @param  list<string>  $meta
     */
    protected static function fieldItemLabelHtml(FieldTypeRegistry $registry, ?string $type, string $title, array $meta): HtmlString
    {
        $iconSvg = static::fieldTypeIconSvg($registry, $type);

        $metaHtml = $meta === []
            ? ''
            : '<span class="moox-builder-field-item__meta">'.e(implode(' · ', $meta)).'</span>';

        return new HtmlString(
            '<span class="moox-builder-field-item">'
            .'<span class="moox-builder-field-item__badge">'.$iconSvg.'</span>'
            .'<span class="moox-builder-field-item__title">'.e($title).'</span>'
            .$metaHtml
            .'</span>'
        );
    }

    protected static function fieldTypeIconSvg(FieldTypeRegistry $registry, ?string $type): string
    {
        $cacheKey = $type ?? '__empty__';

        if (! array_key_exists($cacheKey, static::$fieldTypeIconSvgCache)) {
            static::$fieldTypeIconSvgCache[$cacheKey] = svg(
                static::fieldTypeIcon($registry, $type),
                'moox-builder-field-item__icon',
            )->toHtml();
        }

        return static::$fieldTypeIconSvgCache[$cacheKey];
    }

    protected static function fieldTypeIcon(FieldTypeRegistry $registry, ?string $type): string
    {
        if (blank($type)) {
            return 'heroicon-o-cube';
        }

        try {
            return $registry->get($type)->icon();
        } catch (UnknownFieldTypeException) {
            return 'heroicon-o-cube';
        }
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected static function layoutRepeaterItemLabel(FieldTypeRegistry $registry, array $state): string
    {
        $parts = [];

        $parts[] = filled($state['label'] ?? null)
            ? (string) $state['label']
            : __('builder::builder.field.layout_item');

        if (filled($state['name'] ?? null)) {
            $parts[] = (string) $state['name'];
        }

        $childrenCount = count($state['children'] ?? []);
        if ($childrenCount > 0) {
            $parts[] = trans_choice('builder::builder.field.subfields_count', $childrenCount, [
                'count' => $childrenCount,
            ]);
        }

        return implode(' · ', $parts);
    }

    protected static function fieldTypeLabel(FieldTypeRegistry $registry, string $type): string
    {
        try {
            return $registry->get($type)->label();
        } catch (UnknownFieldTypeException) {
            return $type;
        }
    }

    /**
     * @return list<Component|\Filament\Schemas\Components\Component>
     */
    protected static function layoutSchema(FieldTypeRegistry $registry): array
    {
        return [
            Hidden::make('id'),
            Hidden::make('sort'),
            Grid::make(2)
                ->schema([
                    TextInput::make('label')
                        ->label(__('builder::builder.field.layout_label'))
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                            if (blank($get('name'))) {
                                $set('name', Str::slug((string) $state, '-'));
                            }
                        }),
                ]),
            TextInput::make('name')
                ->label(__('builder::builder.field.layout_key'))
                ->helperText(__('builder::builder.field.layout_key_helper'))
                ->required()
                ->maxLength(255)
                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                ->live(onBlur: true),
            ...static::validationSettingsSchema(),
            Repeater::make('children')
                ->label(__('builder::builder.field.subfields'))
                ->orderColumn('sort')
                ->reorderable()
                ->collapsible()
                ->collapsed()
                ->itemLabel(fn (array $state): HtmlString => static::fieldRepeaterItemLabel($registry, $state))
                ->schema(static::subFieldSchema($registry))
                ->defaultItems(0),
        ];
    }

    /**
     * @return list<Component|\Filament\Schemas\Components\Component>
     */
    protected static function tabChildFieldSchema(FieldTypeRegistry $registry): array
    {
        return [
            Hidden::make('id'),
            Hidden::make('sort'),
            Grid::make(2)
                ->schema([
                    TextInput::make('label')
                        ->label(__('builder::builder.field.label'))
                        ->helperText(__('builder::builder.field.label_helper'))
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                            if (blank($get('name'))) {
                                $set('name', Str::slug((string) $state, '-'));
                            }
                        }),
                    Select::make('type')
                        ->label(__('builder::builder.field.type'))
                        ->options($registry->optionsForTabChildren())
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn ($state, callable $set, callable $get): mixed => static::seedConfigForFieldType((string) $state, $set, $get))
                        ->native(false),
                ]),
            TextInput::make('name')
                ->label(__('builder::builder.field.name'))
                ->helperText(__('builder::builder.field.name_helper'))
                ->required()
                ->maxLength(255)
                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                ->live(onBlur: true),
            static::requirementAndWidthRow(),
            ...static::columnSettingsSchema(),
            ...static::filterSettingsSchema(),
            ...static::visibilitySettingsSchema(),
            ...static::conditionalLogicSchema(),
            ...static::optionFieldSections($registry),
            Section::make(__('builder::builder.field.subfields'))
                ->description(__('builder::builder.field.subfields_helper'))
                ->icon(Heroicon::OutlinedSquares2x2)
                ->collapsed()
                ->schema([
                    Repeater::make('children')
                        ->hiddenLabel()
                        ->orderColumn('sort')
                        ->reorderable()
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): HtmlString => static::fieldRepeaterItemLabel($registry, $state))
                        ->schema(static::subFieldSchema($registry))
                        ->defaultItems(0),
                ])
                ->visible(fn (callable $get): bool => filled($get('type')) && $registry->get($get('type'))->hasSubFields() && $get('type') !== 'flexible_content'),
            Section::make(__('builder::builder.field.layouts'))
                ->description(__('builder::builder.field.layouts_helper'))
                ->icon(Heroicon::OutlinedSquaresPlus)
                ->collapsed()
                ->schema([
                    Repeater::make('layouts')
                        ->hiddenLabel()
                        ->orderColumn('sort')
                        ->reorderable()
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): string => static::layoutRepeaterItemLabel($registry, $state))
                        ->schema(static::layoutSchema($registry))
                        ->defaultItems(0),
                ])
                ->visible(fn (callable $get): bool => filled($get('type')) && $get('type') === 'flexible_content'),
        ];
    }

    /**
     * @return list<Component|\Filament\Schemas\Components\Component>
     */
    protected static function subFieldSchema(FieldTypeRegistry $registry): array
    {
        return [
            Hidden::make('id'),
            Hidden::make('sort'),
            Grid::make(2)
                ->schema([
                    TextInput::make('label')
                        ->label(__('builder::builder.field.label'))
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                            if (blank($get('name'))) {
                                $set('name', Str::slug((string) $state, '-'));
                            }
                        }),
                    Select::make('type')
                        ->label(__('builder::builder.field.type'))
                        ->options($registry->optionsForSubFields())
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn ($state, callable $set, callable $get): mixed => static::seedConfigForFieldType((string) $state, $set, $get))
                        ->native(false),
                ]),
            TextInput::make('name')
                ->label(__('builder::builder.field.name'))
                ->required()
                ->maxLength(255)
                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                ->live(onBlur: true),
            static::requirementAndWidthRow(),
            ...static::validationSettingsSchema(),
            ...static::conditionalLogicSchema(),
            ...static::optionFieldSections($registry),
        ];
    }

    protected static function typeHasSettings(?string $type): bool
    {
        if (blank($type)) {
            return false;
        }

        try {
            return app(FieldTypeRegistry::class)->get($type)->capabilities() !== [];
        } catch (UnknownFieldTypeException) {
            return false;
        }
    }

    protected static function fieldTypeSupportsRequired(?string $type): bool
    {
        if (blank($type)) {
            return false;
        }

        try {
            return app(FieldTypeRegistry::class)->get($type)->storesValue();
        } catch (UnknownFieldTypeException) {
            return false;
        }
    }

    protected static function fieldTypeSupportsColumn(?string $type): bool
    {
        if (blank($type) || in_array($type, ['password', 'rich_text'], true)) {
            return false;
        }

        try {
            $fieldType = app(FieldTypeRegistry::class)->get($type);

            if (! $fieldType->storesValue() || $fieldType->hasSubFields()) {
                return false;
            }
        } catch (UnknownFieldTypeException) {
            return false;
        }

        return TypedValueColumns::isColumnableType($type);
    }

    protected static function fieldTypeSupportsImageColumn(?string $type): bool
    {
        if (blank($type)) {
            return false;
        }

        try {
            $fieldType = app(FieldTypeRegistry::class)->get($type);

            if (! $fieldType->storesValue() || $fieldType->hasSubFields()) {
                return false;
            }
        } catch (UnknownFieldTypeException) {
            return false;
        }

        return TypedValueColumns::isImageColumnType($type);
    }

    protected static function fieldTypeSupportsRelationColumn(?string $type): bool
    {
        if (blank($type)) {
            return false;
        }

        try {
            $fieldType = app(FieldTypeRegistry::class)->get($type);

            if (! $fieldType->storesValue() || $fieldType->hasSubFields()) {
                return false;
            }
        } catch (UnknownFieldTypeException) {
            return false;
        }

        return TypedValueColumns::isRelationColumnType($type);
    }

    protected static function fieldTypeSupportsAnyColumn(?string $type): bool
    {
        return static::fieldTypeSupportsColumn($type)
            || static::fieldTypeSupportsImageColumn($type)
            || static::fieldTypeSupportsRelationColumn($type);
    }

    protected static function fieldTypeSupportsFilter(?string $type): bool
    {
        if (blank($type)) {
            return false;
        }

        return in_array($type, ['select', 'radio', 'button_group', 'toggle', 'relation'], true);
    }

    protected static function fieldHasFilterableChoiceOptions(callable $get): bool
    {
        if (! in_array($get('type'), ['select', 'radio', 'button_group'], true)) {
            return true;
        }

        $options = $get('options') ?? [];

        if (! is_array($options) || $options === []) {
            return false;
        }

        foreach ($options as $option) {
            if (is_array($option) && filled($option['value'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    protected static function fieldFilterCanBeEnabled(callable $get): bool
    {
        if (! static::fieldTypeSupportsFilter($get('type'))) {
            return false;
        }

        if ($get('type') === 'relation') {
            return ! (bool) ($get('config.multiple') ?? false)
                && filled($get('config.related_entity'));
        }

        return static::fieldHasFilterableChoiceOptions($get);
    }

    /**
     * @return list<Section>
     */
    protected static function filterSettingsSchema(): array
    {
        return [
            Section::make(__('builder::builder.field.list_filter'))
                ->description(__('builder::builder.field.list_filter_helper'))
                ->icon(Heroicon::OutlinedFunnel)
                ->collapsible()
                ->visible(fn (callable $get): bool => static::fieldTypeSupportsFilter($get('type')))
                ->schema([
                    Toggle::make('settings.show_in_filter')
                        ->label(__('builder::builder.field.show_in_filter'))
                        ->helperText(fn (callable $get): string => match (true) {
                            $get('type') === 'relation' && (bool) ($get('config.multiple') ?? false) => __('builder::builder.field.show_in_filter_relation_multiple_helper'),
                            $get('type') === 'relation' && blank($get('config.related_entity')) => __('builder::builder.field.show_in_filter_relation_entity_helper'),
                            in_array($get('type'), ['select', 'radio', 'button_group'], true)
                                && ! static::fieldHasFilterableChoiceOptions($get) => __('builder::builder.field.show_in_filter_choice_options_helper'),
                            default => __('builder::builder.field.show_in_filter_helper'),
                        })
                        ->inline(false)
                        ->disabled(fn (callable $get): bool => ! static::fieldFilterCanBeEnabled($get)),
                ]),
        ];
    }

    protected static function fieldTypeSupportsSortableColumn(?string $type): bool
    {
        return static::fieldTypeSupportsColumn($type)
            || static::fieldTypeSupportsRelationColumn($type);
    }

    protected static function fieldTypeSupportsSearchableColumn(?string $type): bool
    {
        return (static::fieldTypeSupportsColumn($type) && $type !== 'toggle')
            || static::fieldTypeSupportsRelationColumn($type);
    }

    /**
     * Presentation options (badge, color, icon) only apply to text-based columns,
     * not to the boolean icon column used for toggle fields.
     */
    protected static function fieldTypeUsesTextColumn(?string $type): bool
    {
        return static::fieldTypeSupportsColumn($type) && ! in_array($type, ['toggle', 'color'], true);
    }

    protected static function fieldTypeSupportsRelationBadge(callable $get): bool
    {
        return $get('type') === 'relation'
            && ! (bool) ($get('config.multiple') ?? false);
    }

    protected static function fieldTypeSupportsTextColumnPresentation(callable $get): bool
    {
        return static::fieldTypeUsesTextColumn($get('type')) && $get('type') !== 'rich_text'
            || static::fieldTypeSupportsRelationBadge($get);
    }

    /**
     * @return array<string, string>
     */
    protected static function columnColorOptions(): array
    {
        return [
            'primary' => __('builder::builder.field.column_color_primary'),
            'gray' => __('builder::builder.field.column_color_gray'),
            'success' => __('builder::builder.field.column_color_success'),
            'warning' => __('builder::builder.field.column_color_warning'),
            'danger' => __('builder::builder.field.column_color_danger'),
            'info' => __('builder::builder.field.column_color_info'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function fieldHintWrapperAttributes(): array
    {
        return [
            'class' => '[&_.fi-fo-field-label-ctn]:!items-center [&_.fi-fo-field-label-ctn]:!justify-start [&_.fi-fo-field-label-ctn]:gap-x-1 [&_.fi-sc-icon]:text-primary-500',
        ];
    }

    protected static function configureFieldHint(Toggle|TextInput|Select $field, string $label, string $tooltip): Toggle|TextInput|Select
    {
        return $field
            ->label($label)
            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: $tooltip)
            ->extraFieldWrapperAttributes(static::fieldHintWrapperAttributes());
    }

    /**
     * Table-column configuration grouped in its own collapsible section, mirroring
     * the existing "Settings"/"Options" sections. Only shown for columnable fields;
     * the behaviour options appear once "Show in table" is enabled.
     *
     * @return list<Section>
     */
    protected static function columnSettingsSchema(): array
    {
        $enabled = fn (callable $get): bool => $get('settings.show_in_table') === true;

        return [
            Section::make(__('builder::builder.field.table_column'))
                ->description(__('builder::builder.field.table_column_helper'))
                ->icon(Heroicon::OutlinedTableCells)
                ->collapsible()
                ->collapsed()
                ->visible(fn (callable $get): bool => static::fieldTypeSupportsAnyColumn($get('type')))
                ->schema([
                    Toggle::make('settings.show_in_table')
                        ->label(__('builder::builder.field.show_in_table'))
                        ->inline(false)
                        ->live(),
                    Grid::make(2)
                        ->schema([
                            Toggle::make('settings.sortable')
                                ->label(__('builder::builder.field.column_sortable'))
                                ->inline(false)
                                ->default(true)
                                ->visible(fn (callable $get): bool => static::fieldTypeSupportsSortableColumn($get('type'))),
                            Toggle::make('settings.searchable')
                                ->label(__('builder::builder.field.column_searchable'))
                                ->inline(false)
                                ->default(true)
                                ->visible(fn (callable $get): bool => static::fieldTypeSupportsSearchableColumn($get('type'))),
                        ])
                        ->visible(fn (callable $get): bool => $enabled($get)
                            && (static::fieldTypeSupportsSortableColumn($get('type'))
                                || static::fieldTypeSupportsSearchableColumn($get('type')))),
                    static::configureFieldHint(
                        Toggle::make('settings.hidden_by_default'),
                        __('builder::builder.field.column_hidden_by_default'),
                        __('builder::builder.field.column_hidden_by_default_helper'),
                    )
                        ->inline(false)
                        ->default(true)
                        ->visible($enabled),
                    Grid::make(3)
                        ->schema([
                            static::configureFieldHint(
                                Toggle::make('settings.badge'),
                                __('builder::builder.field.column_badge'),
                                __('builder::builder.field.column_badge_helper'),
                            )
                                ->inline(false)
                                ->default(false),
                            static::configureFieldHint(
                                Select::make('settings.color'),
                                __('builder::builder.field.column_color'),
                                __('builder::builder.field.column_color_helper'),
                            )
                                ->options(static::columnColorOptions())
                                ->placeholder(__('builder::builder.field.column_color_default'))
                                ->native(false)
                                ->visible(fn (callable $get): bool => static::fieldTypeUsesTextColumn($get('type'))),
                            static::configureFieldHint(
                                TextInput::make('settings.icon'),
                                __('builder::builder.field.column_icon'),
                                __('builder::builder.field.column_icon_helper'),
                            )
                                ->placeholder('heroicon-o-star')
                                ->visible(fn (callable $get): bool => static::fieldTypeUsesTextColumn($get('type'))),
                        ])
                        ->visible(fn (callable $get): bool => $enabled($get)
                            && static::fieldTypeSupportsTextColumnPresentation($get)),
                    Grid::make(2)
                        ->schema([
                            Select::make('settings.image_shape')
                                ->label(__('builder::builder.field.column_image_shape'))
                                ->options(static::columnImageShapeOptions())
                                ->placeholder(__('builder::builder.field.column_image_shape_rectangle'))
                                ->native(false),
                            Select::make('settings.image_size')
                                ->label(__('builder::builder.field.column_image_size'))
                                ->options(static::columnImageSizeOptions())
                                ->default('md')
                                ->selectablePlaceholder(false)
                                ->native(false),
                        ])
                        ->visible(fn (callable $get): bool => $enabled($get)
                            && static::fieldTypeSupportsImageColumn($get('type'))),
                ]),
        ];
    }

    /**
     * Dynamic show/hide rules based on sibling field values in the same
     * field container (root fields, group, repeater row, or flexible layout).
     *
     * @return list<Section>
     */
    protected static function conditionalLogicSchema(): array
    {
        $enabled = fn (callable $get): bool => (bool) $get('settings.conditions.enabled');

        return [
            Section::make(__('builder::builder.field.conditional_logic'))
                ->description(__('builder::builder.field.conditional_logic_helper'))
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->collapsible()
                ->collapsed()
                ->visible(fn (callable $get): bool => static::fieldTypeSupportsRequired($get('type')))
                ->schema([
                    Toggle::make('settings.conditions.enabled')
                        ->label(__('builder::builder.field.conditional_logic_enabled'))
                        ->inline(false)
                        ->live()
                        ->afterStateUpdated(function (mixed $state, callable $set, callable $get): void {
                            if (! $state) {
                                return;
                            }

                            if (blank($get('settings.conditions.action'))) {
                                $set('settings.conditions.action', ConditionalLogic::ACTION_SHOW);
                            }

                            if (blank($get('settings.conditions.logic'))) {
                                $set('settings.conditions.logic', ConditionalLogic::LOGIC_AND);
                            }

                            if (! is_array($get('settings.conditions.rules'))) {
                                $set('settings.conditions.rules', []);
                            }
                        }),
                    Grid::make(2)
                        ->schema([
                            Select::make('settings.conditions.action')
                                ->label(__('builder::builder.field.conditional_logic_action'))
                                ->options(static::conditionalLogicActionOptions())
                                ->default(ConditionalLogic::ACTION_SHOW)
                                ->selectablePlaceholder(false)
                                ->native(false),
                            Select::make('settings.conditions.logic')
                                ->label(__('builder::builder.field.conditional_logic_logic'))
                                ->options(static::conditionalLogicLogicOptions())
                                ->default(ConditionalLogic::LOGIC_AND)
                                ->selectablePlaceholder(false)
                                ->native(false),
                        ])
                        ->visible($enabled),
                    Repeater::make('settings.conditions.rules')
                        ->label(__('builder::builder.field.conditional_logic_rules'))
                        ->helperText(__('builder::builder.field.conditional_logic_rules_helper'))
                        ->defaultItems(0)
                        ->collapsible()
                        ->collapsed()
                        ->visible($enabled)
                        ->schema([
                            Select::make('field')
                                ->label(__('builder::builder.field.conditional_logic_field'))
                                ->options(fn (callable $get): array => static::siblingFieldOptions($get))
                                ->required()
                                ->searchable()
                                ->native(false),
                            Select::make('operator')
                                ->label(__('builder::builder.field.conditional_logic_operator'))
                                ->options(static::conditionalLogicOperatorOptions())
                                ->default('equals')
                                ->required()
                                ->live()
                                ->selectablePlaceholder(false)
                                ->native(false),
                            TextInput::make('value')
                                ->label(__('builder::builder.field.conditional_logic_value'))
                                ->visible(fn (callable $get): bool => in_array(
                                    $get('operator'),
                                    ['equals', 'not_equals', 'contains'],
                                    true,
                                )),
                        ])
                        ->columns(3),
                ]),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function conditionalLogicActionOptions(): array
    {
        return [
            ConditionalLogic::ACTION_SHOW => __('builder::builder.field.conditional_logic_action_show'),
            ConditionalLogic::ACTION_HIDE => __('builder::builder.field.conditional_logic_action_hide'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function conditionalLogicLogicOptions(): array
    {
        return [
            ConditionalLogic::LOGIC_AND => __('builder::builder.field.conditional_logic_logic_and'),
            ConditionalLogic::LOGIC_OR => __('builder::builder.field.conditional_logic_logic_or'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function conditionalLogicOperatorOptions(): array
    {
        return [
            'equals' => __('builder::builder.field.conditional_logic_operator_equals'),
            'not_equals' => __('builder::builder.field.conditional_logic_operator_not_equals'),
            'empty' => __('builder::builder.field.conditional_logic_operator_empty'),
            'not_empty' => __('builder::builder.field.conditional_logic_operator_not_empty'),
            'contains' => __('builder::builder.field.conditional_logic_operator_contains'),
        ];
    }

    /**
     * Lists sibling fields in the nearest ancestor field repeater as
     * conditional-logic trigger options (root `fields`, or nested `children`).
     *
     * @return array<string, string>
     */
    protected static function siblingFieldOptions(callable $get): array
    {
        $fields = static::resolveSiblingFields($get);

        if ($fields === []) {
            return [];
        }

        $currentName = static::resolveCurrentFieldName($get);
        $options = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $name = (string) ($field['name'] ?? '');

            if ($name === '' || $name === $currentName) {
                continue;
            }

            if (! static::fieldTypeSupportsRequired((string) ($field['type'] ?? ''))) {
                continue;
            }

            $options[$name] = (string) ($field['label'] ?? $name);
        }

        asort($options);

        return $options;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected static function resolveSiblingFields(callable $get): array
    {
        for ($depth = 1; $depth <= 12; $depth++) {
            $prefix = str_repeat('../', $depth);

            foreach (['children', 'fields'] as $key) {
                $candidate = $get($prefix.$key);

                if (static::looksLikeFieldRows($candidate)) {
                    /** @var array<int|string, array<string, mixed>> $candidate */
                    return array_values($candidate);
                }
            }
        }

        return [];
    }

    protected static function resolveCurrentFieldName(callable $get): ?string
    {
        $prefix = '';

        for ($depth = 0; $depth <= 8; $depth++) {
            $name = $get($prefix.'name');

            if (is_string($name) && $name !== '') {
                return $name;
            }

            $prefix .= '../';
        }

        return null;
    }

    protected static function looksLikeFieldRows(mixed $value): bool
    {
        if (! is_array($value) || $value === []) {
            return false;
        }

        foreach ($value as $row) {
            if (! is_array($row) || ! array_key_exists('name', $row) || ! array_key_exists('type', $row)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Per-context visibility (admin, frontend, API) as its own collapsible
     * section. Reused for both fields and – via the toggles – field groups.
     *
     * @return list<Section>
     */
    protected static function visibilitySettingsSchema(): array
    {
        return [
            Section::make(__('builder::builder.field.visibility'))
                ->description(__('builder::builder.field.visibility_helper'))
                ->icon(Heroicon::OutlinedEye)
                ->collapsible()
                ->collapsed()
                ->schema(static::visibilityToggles()),
        ];
    }

    /**
     * @return list<Grid>
     */
    protected static function visibilityToggles(): array
    {
        return [
            Grid::make(3)
                ->schema([
                    static::configureFieldHint(
                        Toggle::make('settings.visible_admin'),
                        __('builder::builder.visibility.admin'),
                        __('builder::builder.visibility.admin_helper'),
                    )
                        ->inline(false)
                        ->default(true),
                    Toggle::make('settings.visible_frontend')
                        ->label(__('builder::builder.visibility.frontend'))
                        ->inline(false)
                        ->default(true),
                    Toggle::make('settings.visible_api')
                        ->label(__('builder::builder.visibility.api'))
                        ->inline(false)
                        ->default(true),
                ]),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function placementOptions(): array
    {
        return [
            FieldGroupPlacement::MAIN => __('builder::builder.field_group.placement_main'),
            FieldGroupPlacement::SIDEBAR => __('builder::builder.field_group.placement_sidebar'),
        ];
    }

    /**
     * "Required" toggle and width selector share one row to keep the field
     * basics compact. Either control hides itself when it does not apply.
     */
    protected static function requirementAndWidthRow(): Grid
    {
        return Grid::make(2)
            ->schema([
                Toggle::make('required')
                    ->label(__('builder::builder.field.required'))
                    ->inline(false)
                    ->live()
                    ->visible(fn (callable $get): bool => static::fieldTypeSupportsRequired($get('type'))),
                static::widthField(),
            ]);
    }

    /**
     * Per-field width override. Defaults to "auto", which follows the group's
     * column layout; a fixed fraction pins the field width. Hidden for tab
     * markers, which span the whole row.
     */
    protected static function widthField(): Select
    {
        return Select::make('settings.width')
            ->label(__('builder::builder.field.width'))
            ->helperText(__('builder::builder.field.width_helper'))
            ->options(static::widthOptions())
            ->default(FieldWidth::AUTO)
            ->selectablePlaceholder(false)
            ->native(false)
            ->visible(fn (callable $get): bool => $get('type') !== 'tab');
    }

    /**
     * @return array<string, string>
     */
    protected static function widthOptions(): array
    {
        return [
            FieldWidth::AUTO => __('builder::builder.field.width_auto'),
            FieldWidth::FULL => __('builder::builder.field.width_full'),
            '1/2' => __('builder::builder.field.width_half'),
            '1/3' => __('builder::builder.field.width_third'),
            '2/3' => __('builder::builder.field.width_two_thirds'),
            '1/4' => __('builder::builder.field.width_quarter'),
            '3/4' => __('builder::builder.field.width_three_quarters'),
        ];
    }

    /**
     * @return list<Section>
     */
    protected static function validationSettingsSchema(): array
    {
        $rules = app(FieldValidationRules::class);

        return [
            Section::make(__('builder::builder.field.validation'))
                ->description(__('builder::builder.field.validation_helper'))
                ->icon(Heroicon::OutlinedShieldCheck)
                ->collapsible()
                ->collapsed()
                ->visible(fn (callable $get): bool => $rules->supportsType($get('type')))
                ->schema([
                    Repeater::make('validation.rule_rows')
                        ->label(__('builder::builder.field.validation_rules'))
                        ->helperText(__('builder::builder.field.validation_rules_helper'))
                        ->defaultItems(0)
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): string => static::validationRuleItemLabel($state))
                        ->schema([
                            Select::make('rule')
                                ->label(__('builder::builder.field.validation_rule'))
                                ->options(fn (callable $get): array => app(FieldValidationRules::class)->availableRulesForType(static::resolveValidationRuleFieldType($get)))
                                ->required()
                                ->live()
                                ->native(false),
                            TextInput::make('value')
                                ->label(__('builder::builder.field.validation_value'))
                                ->visible(fn (callable $get): bool => app(FieldValidationRules::class)->ruleNeedsValue((string) $get('rule'))),
                        ])
                        ->columns(2),
                    Textarea::make('validation.raw_rules')
                        ->label(__('builder::builder.field.validation_raw_rules'))
                        ->helperText(__('builder::builder.field.validation_raw_rules_helper'))
                        ->rows(3)
                        ->placeholder("starts_with:foo\nends_with:bar"),
                ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected static function validationRuleItemLabel(array $state): string
    {
        $rule = filled($state['rule'] ?? null)
            ? __('builder::builder.field.validation_rule_'.Str::snake((string) $state['rule']))
            : __('builder::builder.field.validation_rule');

        $value = filled($state['value'] ?? null) ? ': '.(string) $state['value'] : '';

        return $rule.$value;
    }

    protected static function resolveValidationRuleFieldType(callable $get): ?string
    {
        foreach (['../../type', '../../../type', '../../../../type'] as $path) {
            $type = $get($path);

            if (is_string($type) && $type !== '') {
                return $type;
            }
        }

        return null;
    }

    /**
     * Column-count options for a field group's layout (1–4 columns).
     *
     * @return array<int, string>
     */
    protected static function columnsOptions(): array
    {
        $options = [];

        foreach (FieldWidth::GROUP_COLUMNS as $columns) {
            $options[$columns] = trans_choice('builder::builder.field_group.columns_option', $columns, ['count' => $columns]);
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    protected static function columnImageShapeOptions(): array
    {
        return [
            'square' => __('builder::builder.field.column_image_shape_square'),
            'circular' => __('builder::builder.field.column_image_shape_circular'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function columnImageSizeOptions(): array
    {
        return [
            'sm' => __('builder::builder.field.column_image_size_sm'),
            'md' => __('builder::builder.field.column_image_size_md'),
            'lg' => __('builder::builder.field.column_image_size_lg'),
        ];
    }

    /**
     * @return list<Component|\Filament\Schemas\Components\Component>
     */
    protected static function optionFieldSections(FieldTypeRegistry $registry): array
    {
        return [
            Section::make(__('builder::builder.field.settings'))
                ->description(__('builder::builder.field.settings_helper'))
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->collapsible()
                ->schema(fn (callable $get): array => static::reactiveTypeSettingsSchema($get))
                ->visible(fn (callable $get): bool => static::typeHasSettings($get('type'))),
            Section::make(__('builder::builder.field.options'))
                ->description(__('builder::builder.field.options_helper'))
                ->icon(Heroicon::OutlinedListBullet)
                ->collapsed()
                ->schema([
                    Repeater::make('options')
                        ->label(__('builder::builder.field.options'))
                        ->orderColumn('sort')
                        ->reorderable()
                        ->live()
                        ->schema([
                            Hidden::make('id'),
                            TextInput::make('label')
                                ->label(__('builder::builder.field.option_label'))
                                ->required()
                                ->live(onBlur: true),
                            TextInput::make('value')
                                ->label(__('builder::builder.field.option_value'))
                                ->required()
                                ->live(onBlur: true),
                        ])
                        ->columns(2)
                        ->defaultItems(1),
                ])
                ->visible(fn (callable $get): bool => filled($get('type')) && $registry->get($get('type'))->hasOptions()),
        ];
    }

    /**
     * @return list<Component|\Filament\Schemas\Components\Component>
     */
    protected static function reactiveTypeSettingsSchema(callable $get): array
    {
        $type = $get('type');

        if (in_array($type, ['date', 'datetime', 'time'], true)) {
            $get('config.displayFormat');
            $get('config.default');
            $get('config.defaultNow');
        }

        if (in_array($type, ['select', 'radio', 'button_group', 'multiselect', 'checkbox_list'], true)) {
            $get('options');
            $get('config.default');
        }

        if ($type === 'range') {
            $get('config.min');
            $get('config.max');
            $get('config.step');
            $get('config.default');
        }

        if ($type === 'color') {
            $get('config.default');
        }

        if (in_array($type, ['text', 'textarea', 'rich_text', 'email', 'password'], true)) {
            $get('config.maxLength');
            $get('config.default');
        }

        if ($type === 'relation') {
            $get('config.multiple');
            $get('config.related_entity');
        }

        return static::typeSettingsSchema($type);
    }

    /**
     * Ensures relation config keys exist in the repeater item state when the
     * type settings section mounts inside a reactive schema closure.
     *
     * @see https://github.com/filamentphp/filament/issues/3575
     */
    protected static function seedConfigForFieldType(string $type, callable $set, callable $get): void
    {
        if ($type !== 'relation') {
            return;
        }

        $config = $get('config');

        if (! is_array($config)) {
            $config = [];
        }

        $set('config', array_merge([
            'related_entity' => null,
            'multiple' => false,
        ], $config));
    }

    /**
     * @return list<Component|\Filament\Schemas\Components\Component>
     */
    protected static function typeSettingsSchema(?string $type): array
    {
        if (blank($type)) {
            return [];
        }

        if (! array_key_exists($type, static::$typeSettingsCapabilityCache)) {
            try {
                static::$typeSettingsCapabilityCache[$type] = app(FieldTypeRegistry::class)
                    ->get($type)
                    ->capabilities();
            } catch (UnknownFieldTypeException) {
                static::$typeSettingsCapabilityCache[$type] = [];
            }
        }

        $components = [];

        foreach (static::$typeSettingsCapabilityCache[$type] as $capabilityClass) {
            $components = array_merge($components, app($capabilityClass)->builderFieldsFor($type));
        }

        return $components;
    }

    /**
     * Collapse/expand controls live on the section header (Filament-native, right-aligned).
     *
     * @return list<Action>
     */
    protected static function fieldRepeaterHeaderActions(): array
    {
        $visible = fn (Get $get): bool => count($get('fields') ?? []) >= 2;

        return [
            Action::make('collapseAllFields')
                ->label('')
                ->icon(Heroicon::ArrowsPointingIn)
                ->iconButton()
                ->tooltip(__('builder::builder.repeater.collapse_all'))
                ->color('gray')
                ->size(Size::Small)
                ->visible($visible)
                ->alpineClickHandler("\$dispatch('repeater-collapse', 'data.fields')"),
            Action::make('expandAllFields')
                ->label('')
                ->icon(Heroicon::ArrowsPointingOut)
                ->iconButton()
                ->tooltip(__('builder::builder.repeater.expand_all'))
                ->color('gray')
                ->size(Size::Small)
                ->visible($visible)
                ->alpineClickHandler("\$dispatch('repeater-expand', 'data.fields')"),
        ];
    }

    /**
     * @return list<Component>
     */
    public static function locationConstraintSchema(): array
    {
        $targetEntitiesPath = '../../target_entities';

        return [
            Grid::make()
                ->schema([
                    Select::make('param')
                        ->label(__('builder::builder.field_group.location_param'))
                        ->options(fn (Get $get): array => app(LocationConstraintOptions::class)->availableParamOptionsForEntities($get($targetEntitiesPath)))
                        ->helperText(fn (Get $get): string => static::locationConstraintParamHelperText($get($targetEntitiesPath)))
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (callable $set): void {
                            $set('taxonomy', null);
                            $set('operator', '==');
                            $set('value', null);
                        })
                        ->native(false)
                        ->columnSpan(['default' => 1, 'md' => 4]),
                    Select::make('taxonomy')
                        ->label(__('builder::builder.field_group.location_taxonomy'))
                        ->helperText(__('builder::builder.field_group.location_taxonomy_helper'))
                        ->options(fn (Get $get): array => app(LocationConstraintOptions::class)
                            ->taxonomyKeysForEntities($get($targetEntitiesPath)))
                        ->visible(fn (Get $get): bool => $get('param') === 'taxonomy')
                        ->required(fn (Get $get): bool => $get('param') === 'taxonomy')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (callable $set): mixed => $set('value', null))
                        ->native(false)
                        ->columnSpan(['default' => 1, 'md' => 5]),
                    Select::make('operator')
                        ->label(__('builder::builder.field_group.location_operator'))
                        ->options(static::locationConstraintOperatorOptions())
                        ->default('==')
                        ->required()
                        ->live()
                        ->disabled(fn (Get $get): bool => $get('param') === 'user_role'
                            && ! app(LocationConstraintOptions::class)->supportsUserRoles())
                        ->native(false)
                        ->columnSpan(['default' => 1, 'md' => 3]),
                ])
                ->columns(['default' => 1, 'md' => 12]),
            Select::make('value')
                ->label(__('builder::builder.field_group.location_value'))
                ->helperText(fn (Get $get): string => match ($get('param')) {
                    'taxonomy' => __('builder::builder.field_group.location_value_taxonomy_helper'),
                    'record_type' => __('builder::builder.field_group.location_value_record_type_helper'),
                    'record_status' => __('builder::builder.field_group.location_value_record_status_helper'),
                    'user_role' => app(LocationConstraintOptions::class)->userRoleUnavailableReason()
                        ?? __('builder::builder.field_group.location_value_role_helper'),
                    default => __('builder::builder.field_group.location_value_helper'),
                })
                ->options(function (Get $get) use ($targetEntitiesPath): array {
                    return match ($get('param')) {
                        'taxonomy' => filled($get('taxonomy'))
                            ? app(LocationConstraintOptions::class)->searchTermOptionsForTaxonomy(
                                (string) $get('taxonomy'),
                                $get($targetEntitiesPath),
                                '',
                            )
                            : [],
                        'record_type' => app(LocationConstraintOptions::class)->recordTypeOptionsForEntities($get($targetEntitiesPath)),
                        'record_status' => app(LocationConstraintOptions::class)->recordStatusOptionsForEntities($get($targetEntitiesPath)),
                        'user_role' => app(LocationConstraintOptions::class)->roleOptions(),
                        default => [],
                    };
                })
                ->getSearchResultsUsing(function (string $search, Get $get) use ($targetEntitiesPath): array {
                    if ($get('param') !== 'taxonomy' || blank($get('taxonomy'))) {
                        return [];
                    }

                    return app(LocationConstraintOptions::class)->searchTermOptionsForTaxonomy(
                        (string) $get('taxonomy'),
                        $get($targetEntitiesPath),
                        $search,
                    );
                })
                ->visible(fn (Get $get): bool => in_array($get('param'), ['taxonomy', 'record_type', 'record_status', 'user_role'], true)
                    && ($get('param') !== 'taxonomy' || filled($get('taxonomy'))))
                ->required(fn (Get $get): bool => in_array($get('param'), ['taxonomy', 'record_type', 'record_status', 'user_role'], true)
                    && ($get('param') !== 'user_role' || app(LocationConstraintOptions::class)->supportsUserRoles())
                    && ($get('param') !== 'taxonomy' || filled($get('taxonomy'))))
                ->disabled(fn (Get $get): bool => $get('param') === 'user_role'
                    && ! app(LocationConstraintOptions::class)->supportsUserRoles())
                ->multiple(fn (Get $get): bool => in_array($get('operator'), ['in', 'not in'], true))
                ->getOptionLabelUsing(function (mixed $value, Get $get) use ($targetEntitiesPath): ?string {
                    return match ($get('param')) {
                        'taxonomy' => app(LocationConstraintOptions::class)->termLabelForValue(
                            (string) $get('taxonomy'),
                            $get($targetEntitiesPath),
                            $value,
                        ),
                        'record_type' => app(LocationConstraintOptions::class)->recordTypeLabelForValue(
                            $get($targetEntitiesPath),
                            $value,
                        ),
                        'record_status' => app(LocationConstraintOptions::class)->recordStatusLabelForValue(
                            $get($targetEntitiesPath),
                            $value,
                        ),
                        'user_role' => filled($value) ? (string) $value : null,
                        default => null,
                    };
                })
                ->getOptionLabelsUsing(function (array $values, Get $get) use ($targetEntitiesPath): array {
                    return match ($get('param')) {
                        'taxonomy' => app(LocationConstraintOptions::class)->termLabelsForValues(
                            (string) $get('taxonomy'),
                            $get($targetEntitiesPath),
                            $values,
                        ),
                        'record_type' => app(LocationConstraintOptions::class)->recordTypeLabelsForValues(
                            $get($targetEntitiesPath),
                            $values,
                        ),
                        'record_status' => app(LocationConstraintOptions::class)->recordStatusLabelsForValues(
                            $get($targetEntitiesPath),
                            $values,
                        ),
                        'user_role' => collect($values)
                            ->filter(fn (mixed $value): bool => filled($value))
                            ->mapWithKeys(fn (mixed $value): array => [$value => (string) $value])
                            ->all(),
                        default => [],
                    };
                })
                ->searchable(fn (Get $get): bool => in_array($get('param'), ['taxonomy', 'record_type', 'record_status', 'user_role'], true))
                ->preload()
                ->native(false)
                ->columnSpanFull(),
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected static function locationConstraintItemLabel(array $state, mixed $targetEntities): string
    {
        $param = (string) ($state['param'] ?? '');

        if ($param === '') {
            return __('builder::builder.field_group.location_constraints_add');
        }

        $paramLabel = static::locationConstraintParamOptions()[$param] ?? Str::headline($param);

        if ($param === 'taxonomy' && filled($state['taxonomy'] ?? null)) {
            $paramLabel .= ': '.Str::headline((string) $state['taxonomy']);
        }

        $valueLabel = static::locationConstraintValueLabel($state, $targetEntities);

        if ($param === 'taxonomy' && blank($state['taxonomy'] ?? null)) {
            return trim($paramLabel.' '.__('builder::builder.field_group.location_constraint_incomplete_taxonomy'));
        }

        if ($valueLabel === '') {
            return trim($paramLabel.' '.__('builder::builder.field_group.location_constraint_incomplete_value'));
        }

        $operator = (string) ($state['operator'] ?? '==');
        $operatorLabel = static::locationConstraintOperatorOptions()[$operator] ?? $operator;

        return trim(implode(' ', array_filter([$paramLabel, $operatorLabel, $valueLabel])));
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected static function locationConstraintValueLabel(array $state, mixed $targetEntities): string
    {
        $value = $state['value'] ?? null;

        if ($value === null || $value === '') {
            return '';
        }

        $options = app(LocationConstraintOptions::class);

        if (($state['param'] ?? null) === 'taxonomy' && filled($state['taxonomy'] ?? null)) {
            if (is_array($value)) {
                return implode(', ', $options->termLabelsForValues((string) $state['taxonomy'], $targetEntities, $value));
            }

            return $options->termLabelForValue((string) $state['taxonomy'], $targetEntities, $value) ?? (string) $value;
        }

        if (($state['param'] ?? null) === 'record_type') {
            if (is_array($value)) {
                return implode(', ', $options->recordTypeLabelsForValues($targetEntities, $value));
            }

            return $options->recordTypeLabelForValue($targetEntities, $value) ?? (string) $value;
        }

        if (($state['param'] ?? null) === 'record_status') {
            if (is_array($value)) {
                return implode(', ', $options->recordStatusLabelsForValues($targetEntities, $value));
            }

            return $options->recordStatusLabelForValue($targetEntities, $value) ?? (string) $value;
        }

        if (is_array($value)) {
            return implode(', ', array_map(static fn (mixed $item): string => (string) $item, $value));
        }

        return (string) $value;
    }

    /**
     * @return array<string, string>
     */
    public static function locationConstraintParamOptions(): array
    {
        return app(LocationConstraintOptions::class)->availableParamOptions();
    }

    protected static function locationConstraintsHelperText(mixed $entities): string
    {
        $options = app(LocationConstraintOptions::class)->availableParamOptionsForEntities($entities);

        if ($entities === null || $entities === [] || $entities === '') {
            return __('builder::builder.field_group.location_constraints_helper_empty_entities');
        }

        if (array_keys($options) === ['user_role']) {
            return __('builder::builder.field_group.location_constraints_helper_roles_only');
        }

        return __('builder::builder.field_group.location_constraints_helper');
    }

    protected static function locationConstraintParamHelperText(mixed $entities): string
    {
        $options = app(LocationConstraintOptions::class)->availableParamOptionsForEntities($entities);

        if ($entities === null || $entities === [] || $entities === '') {
            return __('builder::builder.field_group.location_param_helper_empty_entities');
        }

        if (array_keys($options) === ['user_role']) {
            return __('builder::builder.field_group.location_param_helper_roles_only');
        }

        return __('builder::builder.field_group.location_param_helper');
    }

    /**
     * @param  list<array<string, mixed>>  $constraints
     * @return list<array<string, mixed>>
     */
    protected static function sanitizeLocationConstraintsForEntities(array $constraints, mixed $entities): array
    {
        $options = app(LocationConstraintOptions::class);
        $allowedParams = array_keys($options->availableParamOptionsForEntities($entities));
        $allowedTaxonomies = $options->taxonomyKeysForEntities($entities);
        $recordTypeOptions = $options->recordTypeOptionsForEntities($entities);
        $recordStatusOptions = $options->recordStatusOptionsForEntities($entities);

        return array_values(array_map(
            static function (array $constraint) use ($allowedParams, $allowedTaxonomies, $recordTypeOptions, $recordStatusOptions): array {
                $param = (string) ($constraint['param'] ?? '');

                if ($param === '' || ! in_array($param, $allowedParams, true)) {
                    return [
                        ...$constraint,
                        'param' => null,
                        'taxonomy' => null,
                        'operator' => '==',
                        'value' => null,
                    ];
                }

                if ($param === 'taxonomy') {
                    $taxonomy = (string) ($constraint['taxonomy'] ?? '');

                    if ($taxonomy === '' || ! array_key_exists($taxonomy, $allowedTaxonomies)) {
                        return [
                            ...$constraint,
                            'taxonomy' => null,
                            'value' => null,
                        ];
                    }
                }

                if ($param === 'record_type' && $recordTypeOptions === []) {
                    return [
                        ...$constraint,
                        'value' => null,
                    ];
                }

                if ($param === 'record_status' && $recordStatusOptions === []) {
                    return [
                        ...$constraint,
                        'value' => null,
                    ];
                }

                return $constraint;
            },
            $constraints,
        ));
    }

    /**
     * @return array<string, string>
     */
    public static function locationConstraintOperatorOptions(): array
    {
        return [
            '==' => __('builder::builder.field_group.location_operator_equals'),
            '!=' => __('builder::builder.field_group.location_operator_not_equals'),
            'in' => __('builder::builder.field_group.location_operator_in'),
            'not in' => __('builder::builder.field_group.location_operator_not_in'),
        ];
    }

    public static function table(Table $table): Table
    {
        $persistence = app(FieldGroupPersistence::class);
        $entityRegistry = app(EntityRegistry::class);
        $localeResolver = app(BuilderLocaleResolver::class);

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('builder::builder.field_group.name'))
                    ->getStateUsing(fn (FieldGroup $record): string => $persistence->localizedGroupName(
                        $record,
                        $localeResolver->current(),
                    ))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhereHas('translations', function (Builder $query) use ($search): void {
                                    $query->where('name', 'like', "%{$search}%");
                                });
                        });
                    })
                    ->sortable()
                    ->description(fn (FieldGroup $record): ?string => $record->slug),
                TextColumn::make('assigned_entities')
                    ->label(__('builder::builder.field_group.assigned_to'))
                    ->getStateUsing(function (FieldGroup $record) use ($entityRegistry, $persistence): string {
                        return $entityRegistry->labelsFor(
                            $persistence->entitiesFromLocationRules($record->location_rules ?? []),
                        );
                    })
                    ->wrap(),
                TextColumn::make('fields_count')
                    ->counts('fields')
                    ->label(__('builder::builder.field_group.fields_count'))
                    ->alignCenter(),
                IconColumn::make('active')
                    ->label(__('builder::builder.field_group.active'))
                    ->boolean()
                    ->alignCenter(),
                TextColumn::make('sort')
                    ->label(__('builder::builder.field_group.sort'))
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort')
            ->reorderable('sort')
            ->recordActions([
                EditAction::make(),
                FieldGroupDefinitionActions::export(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFieldGroups::route('/'),
            'create' => CreateFieldGroup::route('/create'),
            'edit' => EditFieldGroup::route('/{record}/edit'),
        ];
    }
}
