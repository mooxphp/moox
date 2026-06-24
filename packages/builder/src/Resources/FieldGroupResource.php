<?php

declare(strict_types=1);

namespace Moox\Builder\Resources;

use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
use Illuminate\Support\Str;
use Moox\Builder\Exceptions\UnknownFieldTypeException;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Resources\FieldGroupResource\Pages\CreateFieldGroup;
use Moox\Builder\Resources\FieldGroupResource\Pages\EditFieldGroup;
use Moox\Builder\Resources\FieldGroupResource\Pages\ListFieldGroups;
use Moox\Builder\Services\FieldGroupPersistence;

class FieldGroupResource extends Resource
{
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
                                        ->native(false),
                                ]),
                        ])
                        ->columnSpan(1)
                        ->columns(1),
                    Section::make(__('builder::builder.field_group.fields'))
                        ->columnSpan(2)
                        ->headerActions(static::fieldRepeaterHeaderActions())
                        ->schema([
                            Repeater::make('fields')
                                ->hiddenLabel()
                                ->orderColumn('sort')
                                ->reorderable()
                                ->collapsible()
                                ->collapsed()
                                ->cloneable()
                                ->collapseAllAction(fn (Action $action): Action => $action->hidden())
                                ->expandAllAction(fn (Action $action): Action => $action->hidden())
                                ->itemLabel(fn (array $state): string => static::fieldRepeaterItemLabel($registry, $state))
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
                                                ->native(false),
                                        ]),
                                    TextInput::make('name')
                                        ->label(__('builder::builder.field.name'))
                                        ->helperText(__('builder::builder.field.name_helper'))
                                        ->required()
                                        ->maxLength(255)
                                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                                        ->live(onBlur: true),
                                    Toggle::make('required')
                                        ->label(__('builder::builder.field.required'))
                                        ->inline(false)
                                        ->live(),
                                    Section::make(__('builder::builder.field.settings'))
                                        ->collapsed()
                                        ->schema(fn (callable $get): array => static::typeSettingsSchema($get('type')))
                                        ->visible(fn (callable $get): bool => static::typeHasSettings($get('type'))),
                                    Section::make(__('builder::builder.field.options'))
                                        ->collapsed()
                                        ->schema([
                                            Repeater::make('options')
                                                ->label(__('builder::builder.field.options'))
                                                ->orderColumn('sort')
                                                ->reorderable()
                                                ->schema([
                                                    Hidden::make('id'),
                                                    TextInput::make('label')
                                                        ->label(__('builder::builder.field.option_label'))
                                                        ->required(),
                                                    TextInput::make('value')
                                                        ->label(__('builder::builder.field.option_value'))
                                                        ->required(),
                                                ])
                                                ->columns(2)
                                                ->defaultItems(1),
                                        ])
                                        ->visible(fn (callable $get): bool => filled($get('type')) && $registry->get($get('type'))->hasOptions()),
                                    Section::make(fn (callable $get): string => $get('type') === 'tab'
                                        ? __('builder::builder.field.tab_content')
                                        : __('builder::builder.field.subfields'))
                                        ->description(fn (callable $get): ?string => $get('type') === 'tab'
                                            ? __('builder::builder.field.tab_content_helper')
                                            : null)
                                        ->collapsed()
                                        ->schema([
                                            Repeater::make('children')
                                                ->hiddenLabel()
                                                ->orderColumn('sort')
                                                ->reorderable()
                                                ->collapsible()
                                                ->collapsed()
                                                ->itemLabel(fn (array $state): string => static::fieldRepeaterItemLabel($registry, $state))
                                                ->schema(static::tabChildFieldSchema($registry))
                                                ->defaultItems(0),
                                        ])
                                        ->visible(fn (callable $get): bool => filled($get('type')) && $registry->get($get('type'))->hasSubFields() && $get('type') !== 'flexible_content'),
                                    Section::make(__('builder::builder.field.layouts'))
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
    protected static function fieldRepeaterItemLabel(FieldTypeRegistry $registry, array $state): string
    {
        $parts = [];

        $parts[] = filled($state['label'] ?? null)
            ? (string) $state['label']
            : __('builder::builder.field_group.field_item');

        if (filled($state['type'] ?? null)) {
            $parts[] = static::fieldTypeLabel($registry, (string) $state['type']);
        }

        if (filled($state['name'] ?? null)) {
            $parts[] = (string) $state['name'];
        }

        if (($state['required'] ?? false) === true) {
            $parts[] = __('builder::builder.field.required_badge');
        }

        $childrenCount = count($state['children'] ?? []);
        if ($childrenCount > 0) {
            $parts[] = trans_choice('builder::builder.field.subfields_count', $childrenCount, [
                'count' => $childrenCount,
            ]);
        }

        $layoutsCount = count($state['layouts'] ?? []);
        if ($layoutsCount > 0) {
            $parts[] = trans_choice('builder::builder.field.layouts_count', $layoutsCount, [
                'count' => $layoutsCount,
            ]);
        }

        return implode(' · ', $parts);
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
            Repeater::make('children')
                ->label(__('builder::builder.field.subfields'))
                ->orderColumn('sort')
                ->reorderable()
                ->collapsible()
                ->collapsed()
                ->itemLabel(fn (array $state): string => static::fieldRepeaterItemLabel($registry, $state))
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
                        ->native(false),
                ]),
            TextInput::make('name')
                ->label(__('builder::builder.field.name'))
                ->helperText(__('builder::builder.field.name_helper'))
                ->required()
                ->maxLength(255)
                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                ->live(onBlur: true),
            Toggle::make('required')
                ->label(__('builder::builder.field.required'))
                ->inline(false)
                ->live(),
            Section::make(__('builder::builder.field.settings'))
                ->collapsed()
                ->schema(fn (callable $get): array => static::typeSettingsSchema($get('type')))
                ->visible(fn (callable $get): bool => static::typeHasSettings($get('type'))),
            Section::make(__('builder::builder.field.options'))
                ->collapsed()
                ->schema([
                    Repeater::make('options')
                        ->label(__('builder::builder.field.options'))
                        ->orderColumn('sort')
                        ->reorderable()
                        ->schema([
                            Hidden::make('id'),
                            TextInput::make('label')
                                ->label(__('builder::builder.field.option_label'))
                                ->required(),
                            TextInput::make('value')
                                ->label(__('builder::builder.field.option_value'))
                                ->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(1),
                ])
                ->visible(fn (callable $get): bool => filled($get('type')) && $registry->get($get('type'))->hasOptions()),
            Section::make(__('builder::builder.field.subfields'))
                ->collapsed()
                ->schema([
                    Repeater::make('children')
                        ->hiddenLabel()
                        ->orderColumn('sort')
                        ->reorderable()
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state): string => static::fieldRepeaterItemLabel($registry, $state))
                        ->schema(static::subFieldSchema($registry))
                        ->defaultItems(0),
                ])
                ->visible(fn (callable $get): bool => filled($get('type')) && $registry->get($get('type'))->hasSubFields() && $get('type') !== 'flexible_content'),
            Section::make(__('builder::builder.field.layouts'))
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
                        ->native(false),
                ]),
            TextInput::make('name')
                ->label(__('builder::builder.field.name'))
                ->required()
                ->maxLength(255)
                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                ->live(onBlur: true),
            Toggle::make('required')
                ->label(__('builder::builder.field.required'))
                ->inline(false)
                ->live(),
            Section::make(__('builder::builder.field.settings'))
                ->collapsed()
                ->schema(fn (callable $get): array => static::typeSettingsSchema($get('type')))
                ->visible(fn (callable $get): bool => static::typeHasSettings($get('type'))),
            Section::make(__('builder::builder.field.options'))
                ->collapsed()
                ->schema([
                    Repeater::make('options')
                        ->label(__('builder::builder.field.options'))
                        ->orderColumn('sort')
                        ->reorderable()
                        ->schema([
                            Hidden::make('id'),
                            TextInput::make('label')
                                ->label(__('builder::builder.field.option_label'))
                                ->required(),
                            TextInput::make('value')
                                ->label(__('builder::builder.field.option_value'))
                                ->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(1),
                ])
                ->visible(fn (callable $get): bool => filled($get('type')) && $registry->get($get('type'))->hasOptions()),
        ];
    }

    protected static function typeHasSettings(?string $type): bool
    {
        return static::typeSettingsSchema($type) !== [];
    }

    /**
     * @return list<Component|\Filament\Schemas\Components\Component>
     */
    protected static function typeSettingsSchema(?string $type): array
    {
        if (blank($type)) {
            return [];
        }

        $fieldType = app(FieldTypeRegistry::class)->get($type);
        $components = [];

        foreach ($fieldType->capabilities() as $capabilityClass) {
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

    public static function table(Table $table): Table
    {
        $persistence = app(FieldGroupPersistence::class);
        $entityRegistry = app(EntityRegistry::class);

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('builder::builder.field_group.name'))
                    ->searchable()
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
            ->reorderable('sort');
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
