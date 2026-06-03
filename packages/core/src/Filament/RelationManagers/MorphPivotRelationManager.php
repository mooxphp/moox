<?php

declare(strict_types=1);

namespace Moox\Core\Filament\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Moox\Core\Services\MorphPivotRelationService;
use Moox\Core\Support\MorphPivot\MorphPivotRelationRegistry;
use Override;
use RuntimeException;

class MorphPivotRelationManager extends RelationManager
{
    public ?string $morphRelationConfigKey = null;

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
            && filled($component->morphRelationConfigKey)
            && isset($component->ownerRecord)
        ) {
            $config = static::resolveConfig($component->getOwnerRecord(), $component->morphRelationConfigKey);
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
            && filled($component->morphRelationConfigKey)
            && isset($component->ownerRecord)
        ) {
            $service = app(MorphPivotRelationService::class);
            $service->setCurrentResource($component->getOwnerRecord()::getResourceName());

            return $service->getRelationshipMethodName((string) $component->morphRelationConfigKey);
        }

        throw new RuntimeException(
            'MorphPivotRelationManager requires relationshipName or morphRelationConfigKey on the Livewire component.',
        );
    }

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        $component = Livewire::current();

        if ($component instanceof self && filled($component->morphRelationConfigKey)) {
            $config = static::resolveConfig($ownerRecord, $component->morphRelationConfigKey);
            $label = $config['label'] ?? null;

            if (is_string($label) && $label !== '') {
                return static::translateMorphPivotLabel($label);
            }
        }

        return parent::getTitle($ownerRecord, $pageClass);
    }

    public function getRelationship(): Relation|Builder
    {
        $key = (string) $this->morphRelationConfigKey;
        $relationship = $this->morphPivotService()->getRelationshipMethodName($key);

        return $this->getOwnerRecord()->{$relationship}();
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
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
        if ($action instanceof CreateAction) {
            return null;
        }

        $record = $action->getRecord();

        if ($record instanceof Model && ($resource = $this->getResolvedRelatedResource())) {
            if ($action instanceof EditAction && $resource::hasPage('edit')) {
                return $this->getRelatedRecordResourceUrl($record, 'edit');
            }

            if ($action instanceof ViewAction && $resource::hasPage('view')) {
                return $this->getRelatedRecordResourceUrl($record, 'view');
            }
        }

        return parent::getDefaultActionUrl($action);
    }

    #[Override]
    protected function makeTable(): Table
    {
        $table = $this->makeBaseTable()
            ->relationship(fn (): Relation|Builder => $this->getRelationship())
            ->modifyQueryUsing($this->modifyQueryWithActiveTab(...))
            ->queryStringIdentifier(Str::lcfirst(class_basename(static::class)));

        $table->authorizeReorder(fn (): bool => $this->canReorder());

        if ($relatedResource = static::getRelatedResource()) {
            $table
                ->modelLabel($relatedResource::getModelLabel())
                ->pluralModelLabel($relatedResource::getPluralModelLabel());
        }

        return $table->heading($this->getTableHeading() ?? static::getTitle($this->getOwnerRecord(), $this->getPageClass()));
    }

    public function table(Table $table): Table
    {
        $config = static::resolveConfig($this->getOwnerRecord(), $this->morphRelationConfigKey);
        $prefix = (string) ($config['translation_prefix'] ?? '');

        $columns = [];

        foreach ((array) ($config['display_columns'] ?? ['name']) as $column) {
            if (! is_string($column) || $column === '') {
                continue;
            }

            $field = TextColumn::make($column)
                ->label($this->fieldLabel($prefix, $column))
                ->searchable();

            if ($column === 'is_primary') {
                $columns[] = IconColumn::make($column)
                    ->label($this->fieldLabel($prefix, $column))
                    ->boolean();

                continue;
            }

            $columns[] = $field;
        }

        foreach ($this->morphPivotService()->getMorphPivotRelationPivotColumns(
            (string) $this->morphRelationConfigKey,
        ) as $column) {
            $columns[] = IconColumn::make($column)
                ->label($this->fieldLabel($prefix, $column))
                ->boolean();
        }

        $relatedResource = $this->getResolvedRelatedResource();
        $attachAction = $this->configureAttachAction(
            AttachAction::make()
                ->preloadRecordSelect()
                ->schema(fn (AttachAction $action): array => [
                    $action->getRecordSelect(),
                    ...$this->pivotFormFields(),
                ]),
            $config,
        );

        $searchColumns = $config['record_select_search_columns'] ?? null;

        if (is_array($searchColumns) && $searchColumns !== []) {
            $attachAction->recordSelectSearchColumns(array_values(array_map(strval(...), $searchColumns)));
        }

        $headerActions = [$attachAction];

        if ($relatedResource !== null) {
            $headerActions[] = CreateAction::make()
                ->label($this->getCreateActionLabel($relatedResource));
        }

        $table = $table
            ->columns($columns)
            ->headerActions($headerActions);

        $inverseRelationship = $config['inverse_relationship'] ?? null;

        if (is_string($inverseRelationship) && $inverseRelationship !== '') {
            $table->inverseRelationship($inverseRelationship);
        } else {
            // Morph pivots: Filament would guess e.g. Address::companies() — use pivot keys instead.
            $table->allowDuplicates();
        }

        $recordActions = [];

        if ($relatedResource !== null && $relatedResource::hasPage('edit')) {
            $recordActions[] = EditAction::make('editRelated')
                ->label(__('filament-actions::edit.single.label', [
                    'label' => $relatedResource::getModelLabel(),
                ]))
                ->url(fn (Model $record): string => $this->getRelatedRecordResourceUrl($record, 'edit'));
        }

        $recordActions[] = EditAction::make('editPivot')
            ->label($this->getPivotSectionLabel())
            ->schema(fn (Schema $schema): Schema => $schema->components($this->pivotFormFields()));
        $recordActions[] = DetachAction::make();

        return $table
            ->recordActions($recordActions)
            ->toolbarActions([
                DetachBulkAction::make(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function resolveConfig(Model $ownerRecord, ?string $key): array
    {
        if ($key === null || $key === '' || ! method_exists($ownerRecord, 'getResourceName')) {
            return [];
        }

        $config = config($ownerRecord::getResourceName().".morph_relations.{$key}", []);

        return is_array($config) ? MorphPivotRelationRegistry::mergeConfig($config) : [];
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

        $config = static::resolveConfig($this->getOwnerRecord(), $this->morphRelationConfigKey);
        $resource = $config['related_resource'] ?? null;

        if (is_string($resource) && $resource !== '' && class_exists($resource)) {
            return $resource;
        }

        return null;
    }

    protected function getPivotSectionLabel(): string
    {
        $config = static::resolveConfig($this->getOwnerRecord(), $this->morphRelationConfigKey);
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
        $config = static::resolveConfig($this->getOwnerRecord(), $this->morphRelationConfigKey);
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

    protected function morphPivotService(): MorphPivotRelationService
    {
        $service = app(MorphPivotRelationService::class);
        $service->setCurrentResource($this->getOwnerRecord()::getResourceName());

        return $service;
    }

    /**
     * @return list<Checkbox>
     */
    protected function pivotFormFields(): array
    {
        $config = static::resolveConfig($this->getOwnerRecord(), $this->morphRelationConfigKey);
        $prefix = (string) ($config['translation_prefix'] ?? '');

        $fields = [];

        foreach ($this->morphPivotService()->getMorphPivotRelationPivotColumns(
            (string) $this->morphRelationConfigKey,
        ) as $column) {
            $fields[] = Checkbox::make($column)
                ->label($this->fieldLabel($prefix, $column));
        }

        return $fields;
    }

    protected function fieldLabel(string $translationPrefix, string $column): string
    {
        if ($translationPrefix !== '') {
            return __($translationPrefix.'.'.$column);
        }

        return $column;
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

    protected function getRelatedRecordResourceUrl(Model $record, string $page): string
    {
        $resource = $this->getResolvedRelatedResource();

        if ($resource === null || ! $resource::hasPage($page)) {
            return '';
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
        $config = static::resolveConfig($this->getOwnerRecord(), $this->morphRelationConfigKey);
        $inverse = $config['inverse_relationship'] ?? null;

        return ! (is_string($inverse) && $inverse !== '');
    }
}
