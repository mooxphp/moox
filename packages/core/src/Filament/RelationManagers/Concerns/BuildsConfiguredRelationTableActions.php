<?php

declare(strict_types=1);

namespace Moox\Core\Filament\RelationManagers\Concerns;

use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Moox\Core\Relations\Enums\RelationKind;
use Moox\Core\Relations\Enums\RelationTabAction;
use Moox\Core\Relations\RelationTabActionRegistry;
use Moox\Core\Relations\ResolvedRelation;

trait BuildsConfiguredRelationTableActions
{
    /**
     * @return list<Action|AssociateAction|AttachAction|CreateAction>
     */
    protected function buildConfiguredHeaderActions(ResolvedRelation $resolved): array
    {
        $actions = [];

        foreach (RelationTabActionRegistry::headerActions($resolved) as $actionName) {
            $built = $this->buildConfiguredAction($resolved, RelationTabAction::from($actionName));

            if ($built !== null) {
                $actions[] = $built;
            }
        }

        return $actions;
    }

    /**
     * @return list<Action|AssociateAction|CreateAction|DeleteAction|DetachAction|DissociateAction|EditAction|ViewAction>
     */
    protected function buildConfiguredRecordActions(ResolvedRelation $resolved): array
    {
        $actions = [];

        foreach (RelationTabActionRegistry::recordActions($resolved) as $actionName) {
            $built = $this->buildConfiguredAction($resolved, RelationTabAction::from($actionName), record: true);

            if ($built !== null) {
                $actions[] = $built;
            }
        }

        return $actions;
    }

    /**
     * @return list<BulkActionGroup>
     */
    protected function buildConfiguredToolbarActions(ResolvedRelation $resolved): array
    {
        $actions = [];

        if (RelationTabActionRegistry::hasToolbarAction($resolved, RelationTabAction::DetachBulk)) {
            $actions[] = BulkActionGroup::make([
                DetachBulkAction::make(),
            ]);
        }

        return $actions;
    }

    protected function applyConfiguredInverseRelationship(Table $table, ResolvedRelation $resolved): Table
    {
        $inverseRelationship = $resolved->inverseRelationship;

        if (is_string($inverseRelationship) && $inverseRelationship !== '') {
            $table->inverseRelationship($inverseRelationship);
        }

        return $table;
    }

    protected function buildConfiguredAction(ResolvedRelation $resolved, RelationTabAction $action, bool $record = false): mixed
    {
        return match ($action) {
            RelationTabAction::Associate => $record ? null : $this->buildAssociateAction($resolved),
            RelationTabAction::Attach => $record ? null : $this->buildAttachHeaderAction($resolved),
            RelationTabAction::Create => $record ? null : $this->buildCreateHeaderAction($resolved),
            RelationTabAction::View => $record ? $this->buildViewRelatedAction($resolved) : null,
            RelationTabAction::Edit => $record ? $this->buildEditRecordAction($resolved) : null,
            RelationTabAction::EditRelated => $record ? $this->buildEditRelatedResourceAction($resolved) : null,
            RelationTabAction::EditPivot => $record ? $this->buildEditPivotAction($resolved) : null,
            RelationTabAction::Dissociate => $this->buildDissociateAction($resolved, $record),
            RelationTabAction::Detach => $record ? DetachAction::make()->url(null) : null,
            RelationTabAction::Delete => $record ? DeleteAction::make() : null,
            default => null,
        };
    }

    protected function buildAssociateAction(ResolvedRelation $resolved): AssociateAction|Action
    {
        $options = RelationTabActionRegistry::actionOptions($resolved, RelationTabAction::Associate);

        if (($options['strategy'] ?? 'foreign_key') === 'inverse') {
            $action = AssociateAction::make()
                ->recordTitle(fn (Model $record): string => $this->formatOwnerOptionLabel($record))
                ->preloadRecordSelect();

            if (($options['multiple'] ?? false) === true) {
                $action->multiple();
            }

            $this->applyAssociateSearchColumns($action, $resolved);

            return $action;
        }

        $foreignKey = $this->resolveBelongsToForeignKey($resolved);

        return Action::make('associate')
            ->label(__('filament-actions::associate.single.label'))
            ->modalHeading(__('filament-actions::associate.single.modal.heading', [
                'label' => $resolved->label(),
            ]))
            ->modalSubmitActionLabel(__('filament-actions::associate.single.modal.actions.associate.label'))
            ->successNotificationTitle(__('filament-actions::associate.single.notifications.associated.title'))
            ->schema([
                Select::make('recordId')
                    ->label($resolved->label())
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => $this->searchRelatedRecords($resolved, '', $options))
                    ->getSearchResultsUsing(fn (?string $search): array => $this->searchRelatedRecords($resolved, $search ?? '', $options))
                    ->getOptionLabelUsing(function (mixed $value) use ($resolved): ?string {
                        $relatedModel = $resolved->relatedModel;

                        if (! is_string($relatedModel) || $relatedModel === '' || ! filled($value)) {
                            return null;
                        }

                        $record = $relatedModel::query()->find($value);

                        return $record instanceof Model ? $this->formatOwnerOptionLabel($record) : null;
                    })
                    ->required(),
            ])
            ->action(function (array $data) use ($foreignKey): void {
                $owner = $this->getOwnerRecord();
                $recordId = $data['recordId'] ?? null;

                if (! filled($recordId) || (string) $recordId === (string) $owner->getKey()) {
                    return;
                }

                $owner->update([
                    $foreignKey => $recordId,
                ]);
            });
    }

    protected function buildAttachHeaderAction(ResolvedRelation $resolved): AttachAction
    {
        $pivotFields = $resolved->kind === RelationKind::BelongsToMany
            ? $this->belongsToManyPivotFields()
            : $this->pivotFormFields();

        $attachAction = $this->configureAttachAction(
            AttachAction::make()
                ->preloadRecordSelect()
                ->schema(fn (AttachAction $action): array => [
                    $action->getRecordSelect(),
                    ...$pivotFields,
                ]),
            $resolved->config,
        );

        if ($resolved->kind === RelationKind::BelongsToMany) {
            $attachAction->recordTitle(fn (Model $record): string => $this->formatOwnerOptionLabel($record));
        }

        $searchColumns = $resolved->config['record_select_search_columns'] ?? null;

        if (is_array($searchColumns) && $searchColumns !== []) {
            $attachAction->recordSelectSearchColumns(array_values(array_map(strval(...), $searchColumns)));
        }

        return $attachAction;
    }

    protected function buildCreateHeaderAction(ResolvedRelation $resolved): ?CreateAction
    {
        $relatedResource = $this->getResolvedRelatedResource();

        if ($relatedResource === null) {
            return null;
        }

        return CreateAction::make()
            ->label($this->getCreateActionLabel($relatedResource))
            ->url(fn (): string => $this->getConfiguredCreateUrl($resolved, $relatedResource));
    }

    protected function buildViewRelatedAction(ResolvedRelation $resolved): ?ViewAction
    {
        $relatedResource = $this->getResolvedRelatedResource();

        if ($relatedResource === null || ! $relatedResource::hasPage('view')) {
            return null;
        }

        return ViewAction::make()
            ->url(fn (Model $record): ?string => $this->getRelatedRecordResourceUrl($record, 'view'));
    }

    protected function buildEditRecordAction(ResolvedRelation $resolved): EditAction
    {
        $relatedResource = $this->getResolvedRelatedResource();

        if ($relatedResource !== null && $relatedResource::hasPage('edit')) {
            return EditAction::make('edit')
                ->url(fn (Model $record): ?string => $this->getRelatedRecordResourceUrl($record, 'edit'));
        }

        return EditAction::make('edit');
    }

    protected function buildEditRelatedResourceAction(ResolvedRelation $resolved): ?EditAction
    {
        $relatedResource = $this->getResolvedRelatedResource();

        if ($relatedResource === null || ! $relatedResource::hasPage('edit')) {
            return null;
        }

        return EditAction::make('editRelated')
            ->label(__('filament-actions::edit.single.label', [
                'label' => $relatedResource::getModelLabel(),
            ]))
            ->url(fn (Model $record): ?string => $this->getRelatedRecordResourceUrl($record, 'edit'));
    }

    protected function buildEditPivotAction(ResolvedRelation $resolved): EditAction
    {
        if ($resolved->kind === RelationKind::BelongsToMany) {
            return EditAction::make('editPivot')
                ->modal()
                ->url(null)
                ->schema(fn (): array => $this->belongsToManyPivotFields())
                ->mutateRecordDataUsing(function (array $data) use ($resolved): array {
                    return Arr::only($data, $resolved->pivotAttributes);
                });
        }

        return EditAction::make('editPivot')
            ->modal()
            ->url(null)
            ->label($this->getPivotSectionLabel())
            ->schema(fn (Schema $schema): Schema => $schema->components($this->pivotFormFields()));
    }

    protected function buildDissociateAction(ResolvedRelation $resolved, bool $record): DissociateAction|Action|null
    {
        $options = RelationTabActionRegistry::actionOptions($resolved, RelationTabAction::Dissociate);

        if (($options['strategy'] ?? 'inverse') === 'foreign_key') {
            if ($record) {
                return null;
            }

            $foreignKey = $this->resolveBelongsToForeignKey($resolved);

            return Action::make('dissociate')
                ->label(__('filament-actions::dissociate.single.label'))
                ->requiresConfirmation()
                ->color('danger')
                ->successNotificationTitle(__('filament-actions::dissociate.single.notifications.dissociated.title'))
                ->action(fn () => $this->getOwnerRecord()->update([
                    $foreignKey => null,
                ]));
        }

        return $record ? DissociateAction::make() : null;
    }

    protected function applyAssociateSearchColumns(AssociateAction $action, ResolvedRelation $resolved): void
    {
        $searchColumns = $resolved->config['record_select_search_columns'] ?? $resolved->displayColumns;

        if (is_array($searchColumns) && $searchColumns !== []) {
            $action->recordSelectSearchColumns(array_values(array_map(strval(...), $searchColumns)));
        }
    }

    protected function resolveBelongsToForeignKey(ResolvedRelation $resolved): string
    {
        $foreignKey = $resolved->foreignKey ?? $resolved->config['foreign_key'] ?? null;

        if (is_string($foreignKey) && $foreignKey !== '') {
            return $foreignKey;
        }

        return Str::snake($resolved->relationship).'_id';
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, string>
     */
    protected function searchRelatedRecords(ResolvedRelation $resolved, string $search, array $options = []): array
    {
        $relatedModel = $resolved->relatedModel;

        if ($relatedModel === null) {
            return [];
        }

        $owner = $this->getOwnerRecord();
        $ownerKey = $owner->getKey();
        $searchColumns = $resolved->config['record_select_search_columns'] ?? $resolved->displayColumns;
        $searchColumns = is_array($searchColumns) && $searchColumns !== []
            ? $searchColumns
            : [$this->resolveRelatedTitleAttribute($resolved)];

        $query = $relatedModel::query()->limit(50);
        $exclude = array_values(array_filter(
            (array) ($options['exclude'] ?? []),
            fn (mixed $rule): bool => is_string($rule) && $rule !== '',
        ));

        if (in_array('self', $exclude, true)) {
            $query->whereKeyNot($ownerKey);
        }

        if (
            in_array('inverse', $exclude, true)
            && is_string($resolved->inverseRelationship)
            && $resolved->inverseRelationship !== ''
            && $owner->isRelation($resolved->inverseRelationship)
        ) {
            $inverseRelation = $owner->{$resolved->inverseRelationship}();
            $invalidIds = $inverseRelation->pluck($inverseRelation->getRelated()->getQualifiedKeyName());

            if ($invalidIds->isNotEmpty()) {
                $query->whereKeyNot($invalidIds);
            }
        }

        if (trim($search) !== '') {
            $query->where(function (Builder $inner) use ($searchColumns, $search): void {
                foreach ($searchColumns as $column) {
                    if (! is_string($column) || $column === '') {
                        continue;
                    }

                    $inner->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        return $query
            ->get()
            ->mapWithKeys(fn (Model $record): array => [
                (string) $record->getKey() => $this->formatOwnerOptionLabel($record),
            ])
            ->all();
    }

    /**
     * @param  class-string  $relatedResource
     */
    protected function getConfiguredCreateUrl(ResolvedRelation $resolved, string $relatedResource): string
    {
        $params = [];

        foreach ($resolved->config['create_prefill'] ?? [] as $field => $value) {
            if ($value === 'owner.id') {
                $params[$field] = $this->getOwnerRecord()->getKey();
            }
        }

        return $relatedResource::getUrl('create', $params);
    }

    protected function resolveRelatedTitleAttribute(ResolvedRelation $resolved): string
    {
        $relatedModel = $resolved->relatedModel;

        if ($relatedModel === null) {
            return 'name';
        }

        return $this->titleAttributeForOwnerType($relatedModel, []);
    }
}
