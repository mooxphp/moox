<?php

declare(strict_types=1);

namespace Moox\Core\Filament\RelationManagers;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Moox\Core\Filament\RelationManagers\Concerns\BuildsConfiguredRelationTableActions;
use Moox\Core\Relations\Enums\RelationKind;
use Moox\Core\Relations\Enums\RelationTabAction;
use Moox\Core\Relations\RelationTabActionRegistry;
use Moox\Core\Relations\ResolvedRelation;
use Moox\Core\Services\RelationService;
use Override;
use RuntimeException;

use function Livewire\invade;

class ConfigRelationManager extends RelationManager
{
    use BuildsConfiguredRelationTableActions;

    public ?string $relationKey = null;

    /** @deprecated Use {@see $relationKey} */
    public ?string $morphRelationConfigKey = null;

    public bool $inverse = false;

    /**
     * Eloquent relationship method on the owner (e.g. addresses). Set via {@see make()}.
     */
    public ?string $relationshipName = null;

    /**
     * Filament resource for inline create (form + labels). Set via {@see make()} or registry.
     *
     * @var class-string|null
     */
    public ?string $relatedResourceClass = null;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function getRelatedResource(): ?string
    {
        if (isset(static::$relatedResource)) {
            return static::$relatedResource;
        }

        $component = Livewire::current();

        if ($component instanceof self && filled($component->relatedResourceClass) && class_exists($component->relatedResourceClass)) {
            return $component->relatedResourceClass;
        }

        if (
            $component instanceof self
            && filled($component->resolvedRelationKey())
            && isset($component->ownerRecord)
        ) {
            $config = static::resolveConfig($component->getOwnerRecord(), $component->resolvedRelationKey());
            $resource = $config['related_resource'] ?? null;

            if (is_string($resource) && $resource !== '' && class_exists($resource)) {
                return $resource;
            }
        }

        return null;
    }

    #[Override]
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    #[Override]
    public static function getRelationshipName(): string
    {
        if (isset(static::$relationship)) {
            return static::$relationship;
        }

        $component = Livewire::current();

        if ($component instanceof self && filled($component->relationshipName)) {
            return (string) $component->relationshipName;
        }

        if (
            $component instanceof self
            && filled($component->resolvedRelationKey())
            && isset($component->ownerRecord)
        ) {
            $service = app(RelationService::class);
            $service->setCurrentResource($component->getOwnerRecord()::getResourceName());
            $key = (string) $component->resolvedRelationKey();

            if ($component->inverse) {
                $inverse = $service->get($key)->inverseRelationship;

                if (is_string($inverse) && $inverse !== '') {
                    return $inverse;
                }
            }

            return $service->relationshipMethod($key);
        }

        throw new RuntimeException(
            'ConfigRelationManager requires relationshipName or relationKey on the Livewire component.',
        );
    }

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        $component = Livewire::current();

        if ($component instanceof self && filled($component->resolvedRelationKey()) && method_exists($ownerRecord, 'getResourceName')) {
            $service = app(RelationService::class)->forResource($ownerRecord::getResourceName());

            return $service->tabLabel((string) $component->resolvedRelationKey(), $component->inverse);
        }

        return parent::getTitle($ownerRecord, $pageClass);
    }

    public function getRelationship(): Relation|Builder
    {
        $key = $this->resolvedRelationKey();
        $relationship = $this->relationService()->relationshipMethod($key);

        return $this->getOwnerRecord()->{$relationship}();
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        if ($this->resolvedRelation()->kind === RelationKind::PivotHasMany) {
            return $this->pivotHasManyForm($schema);
        }

        parent::form($schema);

        $pivotFields = $this->pivotFormFields();

        if ($pivotFields === []) {
            return $schema;
        }

        return $schema->components([
            ...$schema->getComponents(withHidden: true),
            Section::make($this->getPivotSectionLabel())
                ->schema($pivotFields)
                ->columns(2),
        ]);
    }

    #[Override]
    public function getDefaultActionUrl(Action $action): ?string
    {
        if ($action instanceof CreateAction || $action instanceof DetachAction) {
            return null;
        }

        if ($action instanceof EditAction && $this->isPivotEditAction($action)) {
            return null;
        }

        $record = $action->getRecord();

        if ($record instanceof Model && ($resource = $this->getResolvedRelatedResource())) {
            if (
                $action instanceof EditAction
                && in_array($action->getName(), ['edit', 'editRelated'], true)
                && $resource::hasPage('edit')
            ) {
                return $this->getRelatedRecordResourceUrl($record, 'edit');
            }

            if ($action instanceof ViewAction && $resource::hasPage('view')) {
                return $this->getRelatedRecordResourceUrl($record, 'view');
            }
        }

        return parent::getDefaultActionUrl($action);
    }

    #[Override]
    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        if ($action instanceof DetachAction || $this->isPivotEditAction($action)) {
            return null;
        }

        return parent::getDefaultActionSchemaResolver($action);
    }

    protected function isPivotEditAction(Action $action): bool
    {
        return $action instanceof EditAction
            && in_array($action->getName(), ['editPivot', 'edit_pivot'], true);
    }

    #[Override]
    protected function makeTable(): Table
    {
        $resolved = $this->resolvedRelation();

        if ($resolved->kind === RelationKind::BelongsTo) {
            $table = $this->makeBaseTable()
                ->query(fn (): Builder => $this->belongsToTableQuery($resolved))
                ->modifyQueryUsing($this->modifyQueryWithActiveTab(...))
                ->queryStringIdentifier(Str::lcfirst(class_basename(static::class)));

            $table->authorizeReorder(fn (): bool => false);

            if ($relatedResource = static::getRelatedResource() ?? $this->getResolvedRelatedResource()) {
                $table
                    ->modelLabel($relatedResource::getModelLabel())
                    ->pluralModelLabel($relatedResource::getPluralModelLabel());
            }

            return $table->heading($this->getTableHeading() ?? static::getTitle($this->getOwnerRecord(), $this->getPageClass()));
        }

        $table = $this->makeBaseTable()
            ->relationship(fn (): Relation|Builder => $this->getRelationship())
            ->modifyQueryUsing($this->modifyQueryWithActiveTab(...))
            ->queryStringIdentifier(Str::lcfirst(class_basename(static::class)));

        $table->authorizeReorder(fn (): bool => $this->canReorder());

        if ($relatedResource = static::getRelatedResource() ?? $this->getResolvedRelatedResource()) {
            $table
                ->modelLabel($relatedResource::getModelLabel())
                ->pluralModelLabel($relatedResource::getPluralModelLabel());
        }

        return $table->heading($this->getTableHeading() ?? static::getTitle($this->getOwnerRecord(), $this->getPageClass()));
    }

    public function table(Table $table): Table
    {
        $resolved = $this->resolvedRelation();

        return match ($resolved->kind) {
            RelationKind::PivotHasMany => $this->pivotHasManyTable($table, $resolved),
            RelationKind::MorphPivot => $this->morphPivotTable($table, $resolved),
            default => $this->configureRelationTable($table, $resolved),
        };
    }

    protected function configureRelationTable(Table $table, ResolvedRelation $resolved): Table
    {
        $table = $table
            ->columns($this->buildConfiguredTableColumns($resolved))
            ->headerActions($this->buildConfiguredHeaderActions($resolved))
            ->recordActions($this->buildConfiguredRecordActions($resolved));

        if ($resolved->kind === RelationKind::BelongsToMany) {
            $table->toolbarActions($this->buildConfiguredToolbarActions($resolved));

            $table = $this->configureBelongsToManyTableQuery($table);
            $table = $this->configureRelatedRecordUrl($table, $resolved);
        }

        return $this->applyConfiguredInverseRelationship($table, $resolved);
    }

    /**
     * Filament selects pivot.* plus pivot casts (incl. id => int). That collides with
     * UUID related keys and breaks table record actions (detach, editPivot).
     *
     * When allowDuplicates is true, Filament also skips aliased pivot columns — so
     * role / is_primary / billing_address etc. never reach the table. Always keep
     * the aliased withPivot columns while dropping pivot.*.
     */
    protected function configureBelongsToManyTableQuery(Table $table): Table
    {
        return $table->modifyQueryUsing(function (Builder $query): Builder {
            $relationship = $this->getRelationship();

            if (! $relationship instanceof BelongsToMany) {
                return $query;
            }

            $pivotTable = $relationship->getTable();
            $baseQuery = $query->getQuery();
            $relatedTable = $query->getModel()->getTable();
            /** @var list<string> $aliasedPivotColumns */
            $aliasedPivotColumns = invade($relationship)->aliasedPivotColumns();

            if (is_array($baseQuery->columns)) {
                $baseQuery->columns = array_values(array_filter(
                    $baseQuery->columns,
                    fn (mixed $column): bool => ! is_string($column) || $column !== "{$pivotTable}.*",
                ));
            }

            $requiredColumns = [
                ...$aliasedPivotColumns,
                "{$relatedTable}.*",
            ];

            if ($baseQuery->columns === null || $baseQuery->columns === []) {
                $query->select($requiredColumns);
            } else {
                foreach ($requiredColumns as $column) {
                    if (! in_array($column, $baseQuery->columns, true)) {
                        $query->addSelect($column);
                    }
                }
            }

            $query->withCasts([
                $query->getModel()->getKeyName() => 'string',
            ]);

            return $query;
        });
    }

    /**
     * Filament only hydrates pivot attributes when allowDuplicates is false.
     * Morph address/contact tabs often use allowDuplicates (no inverse relation),
     * which leaves pivot.billing_address / pivot.role / pivot.is_primary empty.
     *
     * @param  EloquentCollection<int, Model>|Paginator|CursorPaginator  $records
     * @return EloquentCollection<int, Model>|Paginator|CursorPaginator
     */
    #[Override]
    protected function hydratePivotRelationForTableRecords(EloquentCollection|Paginator|CursorPaginator $records): EloquentCollection|Paginator|CursorPaginator
    {
        $relationship = $this->getTable()->getRelationship();

        if ($relationship instanceof BelongsToMany) {
            invade($relationship)->hydratePivotRelation($records->all());
        }

        return $records;
    }

    #[Override]
    public function getTableRecordKey(Model|array $record): string
    {
        if (
            ! is_array($record)
            && $this->getTable()->getRelationship() instanceof BelongsToMany
            && ! $this->getTable()->allowsDuplicates()
        ) {
            /** @var BelongsToMany $relationship */
            $relationship = $this->getTable()->getRelationship();
            $relatedPivotKey = $relationship->getRelatedPivotKeyName();
            $pivotAlias = 'pivot_'.$relatedPivotKey;

            $key = $record->getRawOriginal($pivotAlias)
                ?? $record->getRawOriginal($relatedPivotKey)
                ?? $record->getRawOriginal($record->getKeyName());

            if (filled($key)) {
                return (string) $key;
            }
        }

        return parent::getTableRecordKey($record);
    }

    protected function configureRelatedRecordUrl(Table $table, ResolvedRelation $resolved): Table
    {
        $relatedResource = $this->getResolvedRelatedResource();

        if ($relatedResource !== null) {
            if ($relatedResource::hasPage('edit')) {
                return $table->recordUrl(
                    fn (Model $record): ?string => $this->getRelatedRecordResourceUrl($record, 'edit'),
                );
            }

            if ($relatedResource::hasPage('view')) {
                return $table->recordUrl(
                    fn (Model $record): ?string => $this->getRelatedRecordResourceUrl($record, 'view'),
                );
            }
        }

        $table->recordUrl(null);

        if (RelationTabActionRegistry::hasRecordAction($resolved, RelationTabAction::EditPivot)) {
            $table->recordAction('editPivot');
        }

        return $table;
    }

    /**
     * @return list<TextColumn|IconColumn>
     */
    protected function buildConfiguredTableColumns(ResolvedRelation $resolved): array
    {
        $prefix = (string) ($resolved->translationPrefix ?? '');

        $columns = $this->buildModelDisplayColumns($resolved, $prefix);

        if ($resolved->kind === RelationKind::BelongsToMany) {
            return [...$columns, ...$this->buildBelongsToManyPivotColumns($resolved, $prefix)];
        }

        return $columns;
    }

    /**
     * @return list<TextColumn|IconColumn>
     */
    protected function buildBelongsToManyPivotColumns(ResolvedRelation $resolved, string $prefix): array
    {
        $columns = [];
        $sortableColumns = $this->configuredSortableColumns($resolved);

        foreach ($resolved->pivotAttributes as $column) {
            if ($this->isBooleanPivotColumn($resolved, $column)) {
                // Return null when false so the cell stays empty (no X). Only truthy
                // flags render a check icon — avoids boolean falseIcon quirks.
                $iconColumn = IconColumn::make('pivot_'.$column)
                    ->label($this->fieldLabel($prefix, $column))
                    ->getStateUsing(function (Model $record) use ($column): ?bool {
                        $value = $this->pivotAttributeState($record, $column);

                        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? true : null;
                    })
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success');

                if (in_array($column, $sortableColumns, true)) {
                    $iconColumn->sortable(
                        query: function (Builder $query, string $direction) use ($column, $resolved): Builder {
                            $pivotTable = $this->resolvePivotTableName($resolved);

                            if ($pivotTable === null) {
                                return $query;
                            }

                            return $query->orderBy("{$pivotTable}.{$column}", $direction);
                        },
                    );
                }

                $columns[] = $iconColumn;

                continue;
            }

            $textColumn = TextColumn::make('pivot_'.$column)
                ->label($this->fieldLabel($prefix, $column))
                ->badge()
                ->getStateUsing(fn (Model $record): mixed => $this->pivotAttributeState($record, $column));

            if (in_array($column, $sortableColumns, true)) {
                $textColumn->sortable(
                    query: function (Builder $query, string $direction) use ($column, $resolved): Builder {
                        $pivotTable = $this->resolvePivotTableName($resolved);

                        if ($pivotTable === null) {
                            return $query;
                        }

                        return $query->orderBy("{$pivotTable}.{$column}", $direction);
                    },
                );
            }

            $columns[] = $textColumn;
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    protected function configuredSearchableColumns(ResolvedRelation $resolved): array
    {
        $configured = $resolved->config['searchable_columns'] ?? null;

        if ($configured === false) {
            return [];
        }

        if (is_array($configured)) {
            return array_values(array_filter(
                $configured,
                fn (mixed $column): bool => is_string($column) && $column !== '',
            ));
        }

        return array_values(array_filter(
            $resolved->displayColumns,
            fn (string $column): bool => $column !== 'is_primary',
        ));
    }

    /**
     * @return list<string>
     */
    protected function configuredSortableColumns(ResolvedRelation $resolved): array
    {
        $configured = $resolved->config['sortable_columns'] ?? null;

        if ($configured === false) {
            return [];
        }

        if (is_array($configured)) {
            return array_values(array_filter(
                $configured,
                fn (mixed $column): bool => is_string($column) && $column !== '',
            ));
        }

        return array_values(array_unique([
            ...array_filter(
                $resolved->displayColumns,
                fn (string $column): bool => $column !== 'is_primary',
            ),
            ...$resolved->pivotAttributes,
        ]));
    }

    protected function resolvePivotTableName(ResolvedRelation $resolved): ?string
    {
        if (is_string($resolved->pivotTable) && $resolved->pivotTable !== '') {
            return $resolved->pivotTable;
        }

        $relationship = $this->getRelationship();

        if ($relationship instanceof BelongsToMany) {
            return $relationship->getTable();
        }

        return null;
    }

    protected function pivotAttributeState(Model $record, string $column): mixed
    {
        if ($record->relationLoaded('pivot')) {
            $pivot = $record->getRelation('pivot');

            if ($pivot instanceof Model) {
                return $pivot->getAttribute($column);
            }
        }

        return $record->getAttribute('pivot_'.$column);
    }

    protected function isBooleanPivotColumn(ResolvedRelation $resolved, string $column): bool
    {
        if ($column === 'is_primary' || str_ends_with($column, '_address')) {
            return true;
        }

        $pivotModel = $resolved->pivotModel;

        if (! is_string($pivotModel) || $pivotModel === '' || ! class_exists($pivotModel)) {
            return false;
        }

        /** @var Model $instance */
        $instance = new $pivotModel;

        return ($instance->getCasts()[$column] ?? null) === 'boolean';
    }

    protected function morphPivotTable(Table $table, ResolvedRelation $resolved): Table
    {
        $prefix = (string) ($resolved->translationPrefix ?? '');
        $displayColumns = $this->buildModelDisplayColumns($resolved, $prefix);
        $pivotColumns = $this->buildBelongsToManyPivotColumns($resolved, $prefix);

        $columns = ($resolved->config['pivot_columns_first'] ?? false) === true
            ? [...$pivotColumns, ...$displayColumns]
            : [...$displayColumns, ...$pivotColumns];

        $table = $table
            ->columns($columns)
            ->headerActions($this->buildConfiguredHeaderActions($resolved))
            ->recordActions($this->buildConfiguredRecordActions($resolved))
            ->toolbarActions($this->buildConfiguredToolbarActions($resolved));

        $table = $this->configureBelongsToManyTableQuery($table);
        $table = $this->applyConfiguredDefaultOrder($table, $resolved);
        $table = $this->configureRelatedRecordUrl($table, $resolved);

        if (is_string($resolved->inverseRelationship) && $resolved->inverseRelationship !== '') {
            $table->inverseRelationship($resolved->inverseRelationship);
        } else {
            $table->allowDuplicates();
        }

        return $table;
    }

    /**
     * Apply config `default_order` via Filament defaultSort so column header
     * sorting stays primary when the user clicks a sortable column.
     *
     * Rules:
     * - `['column' => 'billing_address', 'direction' => 'desc']` (pivot attrs are qualified)
     * - `['expression' => '(billing_address + postal_address)', 'direction' => 'desc']`
     * - shorthand: `['billing_address', 'desc']` or `'billing_address'`
     */
    protected function applyConfiguredDefaultOrder(Table $table, ResolvedRelation $resolved): Table
    {
        $order = $resolved->config['default_order'] ?? null;

        if (! is_array($order) || $order === []) {
            return $table;
        }

        return $table->defaultSort(function (Builder $query) use ($order, $resolved): Builder {
            $pivotTable = $this->resolvePivotTableName($resolved);

            foreach ($order as $rule) {
                $direction = 'asc';
                $column = null;
                $expression = null;

                if (is_string($rule)) {
                    $column = $rule;
                } elseif (is_array($rule)) {
                    $column = is_string($rule['column'] ?? null) ? $rule['column'] : (is_string($rule[0] ?? null) ? $rule[0] : null);
                    $expression = is_string($rule['expression'] ?? null) ? $rule['expression'] : null;
                    $rawDirection = $rule['direction'] ?? $rule[1] ?? 'asc';
                    $direction = strtolower((string) $rawDirection) === 'desc' ? 'desc' : 'asc';
                } else {
                    continue;
                }

                if (is_string($expression) && $expression !== '') {
                    $qualifiedExpression = $this->qualifyPivotOrderExpression($expression, $pivotTable, $resolved->pivotAttributes);
                    $direction === 'desc'
                        ? $query->orderByDesc(DB::raw($qualifiedExpression))
                        : $query->orderBy(DB::raw($qualifiedExpression));

                    continue;
                }

                if (! is_string($column) || $column === '') {
                    continue;
                }

                $qualified = $column;
                if (! str_contains($column, '.') && is_string($pivotTable) && $pivotTable !== ''
                    && in_array($column, $resolved->pivotAttributes, true)) {
                    $qualified = "{$pivotTable}.{$column}";
                }

                $query->orderBy($qualified, $direction);
            }

            return $query;
        });
    }

    /**
     * @param  list<string>  $pivotAttributes
     */
    protected function qualifyPivotOrderExpression(string $expression, ?string $pivotTable, array $pivotAttributes): string
    {
        if (! is_string($pivotTable) || $pivotTable === '' || $pivotAttributes === []) {
            return $expression;
        }

        $qualified = $expression;

        foreach ($pivotAttributes as $attribute) {
            $qualified = preg_replace(
                '/(?<![`\w.])'.preg_quote($attribute, '/').'(?![`\w])/',
                "`{$pivotTable}`.`{$attribute}`",
                $qualified,
            ) ?? $qualified;
        }

        return $qualified;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function resolveConfig(Model $ownerRecord, ?string $key): array
    {
        if ($key === null || $key === '' || ! method_exists($ownerRecord, 'getResourceName')) {
            return [];
        }

        return app(RelationService::class)
            ->forResource($ownerRecord::getResourceName())
            ->get((string) $key)
            ->config;
    }

    public function resolvedRelationKey(): string
    {
        if (filled($this->relationKey)) {
            return (string) $this->relationKey;
        }

        if (filled($this->morphRelationConfigKey)) {
            return (string) $this->morphRelationConfigKey;
        }

        return '';
    }

    protected function resolvedRelation(): ResolvedRelation
    {
        return $this->relationService()->get($this->resolvedRelationKey());
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function configureAttachAction(AttachAction $attachAction, array $config): AttachAction
    {
        $attachAction = $attachAction->recordTitle(
            fn (?Model $record): ?string => $record ? $this->formatRecordSelectLabel($record, $config) : null,
        );

        if (isset($config['inverse_relationship']) && is_string($config['inverse_relationship']) && $config['inverse_relationship'] !== '') {
            return $attachAction;
        }

        return $attachAction->recordSelectOptionsQuery(function (Builder $query): Builder {
            $relationship = $this->getRelationship();

            if (! $relationship instanceof BelongsToMany) {
                return $query;
            }

            $attachedIds = $relationship->allRelatedIds();

            if ($attachedIds->isEmpty()) {
                return $query;
            }

            return $query->whereNotIn(
                $relationship->getRelated()->getQualifiedKeyName(),
                $attachedIds,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function formatRecordSelectLabel(Model $record, array $config): string
    {
        $method = $config['record_select_label'] ?? null;

        if (is_string($method) && $method !== '' && method_exists($record, $method)) {
            $label = $record->{$method}();

            if (is_string($label) && $label !== '') {
                return $label;
            }
        }

        $columns = $config['record_select_label_columns'] ?? $config['display_columns'] ?? ['name'];
        $parts = [];

        foreach ((array) $columns as $column) {
            if (! is_string($column) || $column === '' || in_array($column, ['is_primary'], true)) {
                continue;
            }

            $value = $record->getAttribute($column);

            if ($value !== null && $value !== '') {
                $parts[] = (string) $value;
            }
        }

        if ($parts !== []) {
            return implode(', ', $parts);
        }

        return (string) $record->getKey();
    }

    /**
     * @return class-string|null
     */
    protected function getResolvedRelatedResource(): ?string
    {
        if (filled($this->relatedResourceClass) && class_exists($this->relatedResourceClass)) {
            return $this->relatedResourceClass;
        }

        $config = static::resolveConfig($this->getOwnerRecord(), $this->resolvedRelationKey());
        $resource = $config['related_resource'] ?? null;

        if (is_string($resource) && $resource !== '' && class_exists($resource)) {
            return $resource;
        }

        return null;
    }

    protected function getPivotSectionLabel(): string
    {
        $config = static::resolveConfig($this->getOwnerRecord(), $this->resolvedRelationKey());
        $prefix = (string) ($config['translation_prefix'] ?? '');

        if ($prefix !== '' && Lang::has($prefix.'.assignments')) {
            return __($prefix.'.assignments');
        }

        return __('filament-actions::attach.single.modal.heading', [
            'label' => static::getRelationshipTitle(),
        ]);
    }

    /**
     * @param  class-string  $relatedResource
     */
    protected function getCreateActionLabel(string $relatedResource): string
    {
        $config = static::resolveConfig($this->getOwnerRecord(), $this->resolvedRelationKey());
        $prefix = (string) ($config['translation_prefix'] ?? '');
        $model = $config['model'] ?? null;

        if (is_string($prefix) && $prefix !== '' && is_string($model) && $model !== '') {
            $key = $prefix.'.create_'.Str::snake(class_basename($model));

            if (Lang::has($key)) {
                return __($key);
            }
        }

        return __('filament-actions::create.single.label', [
            'label' => $relatedResource::getModelLabel(),
        ]);
    }

    protected function relationService(): RelationService
    {
        $service = app(RelationService::class);
        $service->setCurrentResource($this->getOwnerRecord()::getResourceName());

        return $service;
    }

    /**
     * @return list<Checkbox>
     */
    protected function pivotFormFields(): array
    {
        $config = static::resolveConfig($this->getOwnerRecord(), $this->resolvedRelationKey());
        $prefix = (string) ($config['translation_prefix'] ?? '');

        $fields = [];

        foreach ($this->relationService()->pivotAttributes($this->resolvedRelationKey()) as $column) {
            $fields[] = Checkbox::make($column)
                ->label($this->fieldLabel($prefix, $column));
        }

        return $fields;
    }

    protected function fieldLabel(string $translationPrefix, string $column): string
    {
        if ($translationPrefix !== '') {
            $key = $translationPrefix.'.'.$column;

            if (Lang::has($key)) {
                return __($key);
            }
        }

        return Str::headline($column);
    }

    protected static function translateMorphPivotLabel(string $label): string
    {
        if (str_starts_with($label, 'trans//')) {
            $key = substr($label, 7);
            $translated = trans($key);

            return $translated !== $key ? $translated : $key;
        }

        return $label;
    }

    protected function getRelatedRecordResourceUrl(Model $record, string $page): ?string
    {
        $resource = $this->getResolvedRelatedResource();

        if ($resource === null || ! $resource::hasPage($page)) {
            return null;
        }

        return $resource::getUrl($page, [
            'record' => $this->resolveRelatedRecordForUrl($record),
        ], shouldGuessMissingParameters: false);
    }

    /**
     * Related model for resource URLs — not the pivot row when {@see allowDuplicates()} is on.
     */
    protected function resolveRelatedRecordForUrl(Model $record): Model
    {
        $resource = $this->getResolvedRelatedResource();

        if ($resource === null) {
            return $record;
        }

        /** @var class-string<Model> $relatedClass */
        $relatedClass = $resource::getModel();
        $relationship = $this->getRelationship();

        if (! $relationship instanceof BelongsToMany) {
            return $record;
        }

        $relatedId = $this->resolveRelatedRecordPrimaryKey($record, $relationship);

        return $relatedClass::query()->findOrFail($relatedId);
    }

    protected function resolveRelatedRecordPrimaryKey(Model $record, BelongsToMany $relationship): string|int
    {
        $pivotRelatedKey = $relationship->getRelatedPivotKeyName();

        if ($record->relationLoaded('pivot') && filled($record->pivot?->getAttribute($pivotRelatedKey))) {
            return $record->pivot->getAttribute($pivotRelatedKey);
        }

        if (filled($record->getAttribute($pivotRelatedKey))) {
            return $record->getAttribute($pivotRelatedKey);
        }

        if ($this->usesDuplicatePivotRowKeys()) {
            $pivotClass = $relationship->getPivotClass();
            $pivotKeyName = app($pivotClass)->getKeyName();
            $pivotRowKey = $record->getAttribute($pivotKeyName) ?? $record->getKey();

            $relatedId = $pivotClass::query()
                ->whereKey($pivotRowKey)
                ->value($pivotRelatedKey);

            if (filled($relatedId)) {
                return $relatedId;
            }
        }

        $relatedKeyName = $relationship->getRelated()->getKeyName();

        return $record->getAttribute($relatedKeyName) ?? $record->getKey();
    }

    protected function usesDuplicatePivotRowKeys(): bool
    {
        $config = static::resolveConfig($this->getOwnerRecord(), $this->resolvedRelationKey());
        $inverse = $config['inverse_relationship'] ?? null;

        return ! (is_string($inverse) && $inverse !== '');
    }

    protected function pivotHasManyForm(Schema $schema): Schema
    {
        $resolved = $this->resolvedRelation();
        $morphName = (string) ($resolved->morphType ?? 'addressable');
        $ownerTypes = $resolved->ownerTypes;

        $fields = [
            MorphToSelect::make($morphName)
                ->label(__('core::core.owner'))
                ->types(
                    collect($ownerTypes)
                        ->map(fn (array $definition, string $class): Type => Type::make($class)
                            ->label($definition['label'])
                            ->titleAttribute($this->titleAttributeForOwnerType($class, $definition))
                            ->getOptionLabelFromRecordUsing(fn (Model $record): string => $this->formatOwnerOptionLabel($record)))
                        ->values()
                        ->all()
                )
                ->required()
                ->visible(fn (): bool => $ownerTypes !== []),
        ];

        foreach ($resolved->pivotAttributes as $column) {
            $fields[] = Checkbox::make($column)
                ->label($this->fieldLabel((string) ($resolved->translationPrefix ?? ''), $column));
        }

        return $schema->components($fields);
    }

    protected function pivotHasManyTable(Table $table, ResolvedRelation $resolved): Table
    {
        $morphName = (string) ($resolved->morphType ?? 'addressable');
        $prefix = (string) ($resolved->translationPrefix ?? '');

        $columns = [
            TextColumn::make("{$morphName}_type")
                ->label($this->fieldLabel($prefix, 'owner'))
                ->formatStateUsing(fn (?string $state): string => class_basename((string) $state))
                ->searchable(),
            TextColumn::make("{$morphName}_id")
                ->label('ID')
                ->searchable(),
            TextColumn::make($morphName)
                ->label($this->fieldLabel($prefix, 'owner_name'))
                ->formatStateUsing(function (Model $record) use ($morphName): string {
                    $owner = $record->{$morphName};

                    if ($owner instanceof Model) {
                        return $this->formatOwnerOptionLabel($owner);
                    }

                    return (string) ($record->getAttribute($morphName.'_id') ?? '');
                })
                ->searchable(),
        ];

        foreach ($resolved->pivotAttributes as $column) {
            $columns[] = IconColumn::make($column)
                ->label($this->fieldLabel($prefix, $column))
                ->boolean();
        }

        $headerActions = $this->buildConfiguredHeaderActions($resolved);

        foreach ($headerActions as $headerAction) {
            if ($headerAction instanceof CreateAction) {
                $headerAction->visible(fn (): bool => $resolved->ownerTypes !== []);
            }
        }

        return $table
            ->columns($columns)
            ->headerActions($headerActions)
            ->recordActions($this->buildConfiguredRecordActions($resolved));
    }

    /**
     * @param  array{label: string, title_attribute?: string|null}  $definition
     */
    protected function titleAttributeForOwnerType(string $class, array $definition): string
    {
        if (isset($definition['title_attribute']) && is_string($definition['title_attribute']) && $definition['title_attribute'] !== '') {
            return $definition['title_attribute'];
        }

        $model = app($class);
        $schema = DatabaseSchema::connection($model->getConnectionName());

        foreach (['display_name', 'name', 'title'] as $column) {
            if ($schema->hasColumn($model->getTable(), $column)) {
                return $column;
            }
        }

        return $model->getKeyName();
    }

    protected function formatOwnerOptionLabel(Model $record): string
    {
        if (method_exists($record, 'displayLabel')) {
            return (string) $record->displayLabel();
        }

        foreach (['display_name', 'name', 'title'] as $attribute) {
            $value = $record->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return (string) $record->getKey();
    }

    /**
     * @return list<Select|Toggle>
     */
    protected function belongsToManyPivotFields(): array
    {
        $resolved = $this->resolvedRelation();
        $prefix = (string) ($resolved->translationPrefix ?? '');
        $resource = $this->getOwnerRecord()::getResourceName();
        $fields = [];

        foreach ($resolved->pivotAttributes as $column) {
            if ($column === 'role') {
                $roles = config("{$resource}.company_roles", config("{$resource}.roles", ['general']));
                $fields[] = Select::make('role')
                    ->label($this->fieldLabel($prefix, $column))
                    ->options(collect(is_array($roles) ? $roles : [])
                        ->mapWithKeys(fn (string $role): array => [$role => __("{$resource}::roles.{$role}")])
                        ->all())
                    ->default(is_array($roles) ? ($roles[0] ?? 'general') : 'general')
                    ->required();

                continue;
            }

            if ($column === 'is_primary') {
                $fields[] = Toggle::make('is_primary')
                    ->label($this->fieldLabel($prefix, $column));

                continue;
            }

            $fields[] = Checkbox::make($column)
                ->label($this->fieldLabel($prefix, $column));
        }

        return $fields;
    }

    protected function belongsToTableQuery(ResolvedRelation $resolved): Builder
    {
        $relatedModel = $resolved->relatedModel;

        if ($relatedModel === null) {
            return Model::query()->whereRaw('1 = 0');
        }

        $foreignKey = $this->resolveBelongsToForeignKey($resolved);
        $relatedId = $this->getOwnerRecord()->getAttribute($foreignKey);

        if (! filled($relatedId)) {
            return $relatedModel::query()->whereRaw('1 = 0');
        }

        return $relatedModel::query()->whereKey($relatedId);
    }

    /**
     * @return list<TextColumn|IconColumn>
     */
    protected function buildModelDisplayColumns(ResolvedRelation $resolved, string $prefix): array
    {
        $columns = [];
        $displayColumns = $resolved->displayColumns !== [] ? $resolved->displayColumns : ['display_name', 'name'];
        $badgeColumns = array_values(array_filter(
            (array) ($resolved->config['badge_columns'] ?? []),
            fn (mixed $column): bool => is_string($column) && $column !== '',
        ));
        $searchableColumns = $this->configuredSearchableColumns($resolved);
        $sortableColumns = $this->configuredSortableColumns($resolved);

        foreach ($displayColumns as $column) {
            if (! is_string($column) || $column === '') {
                continue;
            }

            if ($column === 'is_primary') {
                $primaryColumn = IconColumn::make($column)
                    ->label($this->fieldLabel($prefix, $column))
                    ->boolean()
                    ->falseIcon(false);

                if (in_array($column, $sortableColumns, true)) {
                    $primaryColumn->sortable();
                }

                $columns[] = $primaryColumn;

                continue;
            }

            $attribute = $column;

            $columnDefinition = TextColumn::make($column)
                ->label($this->fieldLabel($prefix, $column))
                ->formatStateUsing(fn (mixed $state, Model $record): mixed => $record->getAttribute($attribute));

            if (in_array($column, $badgeColumns, true)) {
                $columnDefinition->badge();
            }

            if (in_array($column, $searchableColumns, true)) {
                $columnDefinition->searchable();
            }

            if (in_array($column, $sortableColumns, true)) {
                $columnDefinition->sortable();
            }

            $columns[] = $columnDefinition;
        }

        foreach ($badgeColumns as $column) {
            if (in_array($column, $displayColumns, true)) {
                continue;
            }

            $attribute = $column;

            $badgeColumn = TextColumn::make($column)
                ->label($this->fieldLabel($prefix, $column))
                ->formatStateUsing(fn (mixed $state, Model $record): mixed => $record->getAttribute($attribute))
                ->badge();

            if (in_array($column, $searchableColumns, true)) {
                $badgeColumn->searchable();
            }

            if (in_array($column, $sortableColumns, true)) {
                $badgeColumn->sortable();
            }

            $columns[] = $badgeColumn;
        }

        if ($columns === []) {
            $columns[] = TextColumn::make('display_name')
                ->label($this->fieldLabel($prefix, 'display_name'))
                ->formatStateUsing(fn (mixed $state, Model $record): string => $this->formatOwnerOptionLabel($record))
                ->searchable()
                ->sortable();
        }

        return $columns;
    }
}
