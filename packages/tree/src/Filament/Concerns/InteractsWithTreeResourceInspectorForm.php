<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Concerns;

use Filament\Notifications\Notification;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Moox\Tree\Actions\Tree\PersistTreeResourceCreateAction;
use Moox\Tree\Actions\Tree\PersistTreeResourceUpdateAction;
use Moox\Tree\Support\TreeInlineFormResourceAdapter;
use Moox\Tree\Support\TreeResourcePageExecutor;

trait InteractsWithTreeResourceInspectorForm
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public ?Model $record = null;

    public function usesResourceInspectorPanel(): bool
    {
        return $this->configuration()->usesResourceInspector();
    }

    public function form(Schema $schema): Schema
    {
        return TreeInlineFormResourceAdapter::resolve(
            $this->getInspectorResourceClass(),
        )::form($schema);
    }

    public function isCreatingInlineResourceRecord(): bool
    {
        return $this->isCreatingInspector;
    }

    public function defaultForm(Schema $schema): Schema
    {
        if (! $this->shouldConfigureInspectorResourceForm()) {
            if (method_exists(parent::class, 'defaultForm')) {
                return parent::defaultForm($schema);
            }

            return $schema;
        }

        $resourceClass = $this->getInspectorResourceClass();

        $model = $this->isCreatingInspector
            ? $resourceClass::getModel()
            : ($this->record ?? $resourceClass::getModel());

        $hasInlineLabels = method_exists($this, 'hasInlineLabels') && $this->hasInlineLabels();

        if (! $schema->hasCustomColumns()) {
            $schema->columns($hasInlineLabels ? 1 : 2);
        }

        return $schema
            ->inlineLabel($hasInlineLabels)
            ->statePath('data')
            ->model($model)
            ->operation($this->isCreatingInspector ? 'create' : 'edit');
    }

    public function fillInspectorFormForCreate(?int $parentId = null): void
    {
        if (! $this->supportsInspectorForm()) {
            return;
        }

        $this->forgetCachedInspectorForm();

        $this->record = null;
        $this->data = [];
        $this->resourceInspectorForm()->fill();

        if ($parentId === null) {
            return;
        }

        $parentColumn = $this->configuration()->getParentColumn();

        $this->resourceInspectorForm()->fill([
            $parentColumn => $parentId,
        ]);
    }

    public function fillInspectorFormForSelectedRecord(): void
    {
        if (! $this->supportsInspectorForm()) {
            return;
        }

        $this->forgetCachedInspectorForm();

        $record = $this->getSelectedRecord();

        if ($record === null) {
            $this->record = null;
            $this->data = [];
            $this->resourceInspectorForm()->fill();

            return;
        }

        $this->fillInspectorFormForRecord($record);
    }

    public function fillInspectorFormForRecord(Model $record): void
    {
        if (! $this->supportsInspectorForm()) {
            return;
        }

        $editPageClass = $this->configuration()->getInspectorPageClass();

        if ($editPageClass === null) {
            return;
        }

        $executor = app(TreeResourcePageExecutor::class);
        $page = $executor->makePage($editPageClass, $this);
        $executor->mountPageRecord($page, $record);

        $data = $executor->mutateFormDataBeforeFill($page, [
            ...$record->attributesToArray(),
        ]);

        $this->record = $record;
        $this->resourceInspectorForm()->model($record)->fill($data);
    }

    public function create(bool $another = false): void
    {
        $this->authorizeTreeIndex();

        if (! $this->supportsInspectorForm()) {
            return;
        }

        $formData = $this->resourceInspectorForm()->getState();

        $record = app(PersistTreeResourceCreateAction::class)->handle(
            $this->configuration(),
            $formData,
            $this,
            $this->creatingParentId,
        );

        $this->record = $record;
        $this->resourceInspectorForm()->model($record)->saveRelationships();

        $this->completeInspectorCreate((int) $record->getKey());

        Notification::make()
            ->title(__('filament-panels::resources/pages/create-record.notifications.created.title'))
            ->success()
            ->send();
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        $this->authorizeTreeIndex();

        if (! $this->supportsInspectorForm()) {
            return;
        }

        $record = $this->getSelectedRecord();

        if ($record === null) {
            return;
        }

        $formData = $this->resourceInspectorForm()->getState();

        app(PersistTreeResourceUpdateAction::class)->handle(
            $this->configuration(),
            $record,
            $formData,
            $this,
        );

        $this->fillInspectorFormForSelectedRecord();

        if ($shouldSendSavedNotification) {
            Notification::make()
                ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))
                ->success()
                ->send();
        }
    }

    public function cancelInlineResourceForm(): void
    {
        if ($this->isCreatingInspector) {
            $this->cancelInspectorCreate();

            return;
        }

        $this->fillInspectorFormForSelectedRecord();
    }

    public function completeInlineResourceDeletion(): void
    {
        $nextId = $this->configuration()
            ->applyTreeOrdering($this->query())
            ->value('id');

        $this->treeSelectedId = $nextId === null ? null : (int) $nextId;
        $this->isCreatingInspector = false;
        $this->creatingParentId = null;
        $this->record = null;
        $this->data = [];
        $this->loadInspectorOrStubForm();
    }

    public function cancelInspectorCreate(): void
    {
        $this->isCreatingInspector = false;
        $this->creatingParentId = null;
        $this->record = null;
        $this->data = [];

        if ($this->supportsInspectorForm()) {
            $this->forgetCachedInspectorForm();
            $this->resourceInspectorForm()->fill();
        }
    }

    public function supportsInspectorForm(): bool
    {
        return $this instanceof HasSchemas && $this->shouldConfigureInspectorResourceForm();
    }

    public function getRecord(): ?Model
    {
        if ($this->record instanceof Model) {
            return $this->record;
        }

        if (
            $this->usesResourceInspectorPanel()
            && ! $this->isCreatingInspector
            && $this->treeSelectedId !== null
        ) {
            return $this->getSelectedRecord();
        }

        return null;
    }

    public function hasRecord(): bool
    {
        return $this->getRecord() instanceof Model;
    }

    protected function completeInspectorCreate(int $recordId): void
    {
        $this->isCreatingInspector = false;
        $this->creatingParentId = null;
        $this->treeSelectedId = $recordId;
        $this->fillInspectorFormForSelectedRecord();
    }

    public function inspectorPanel(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler(fn (): string => $this->isCreatingInspector ? 'create' : 'save'),
        ]);
    }

    protected function shouldConfigureInspectorResourceForm(): bool
    {
        return $this->usesResourceInspectorPanel();
    }

    protected function forgetCachedInspectorForm(): void
    {
        $this->cacheSchema('form', null);
        $this->cacheSchema('inspectorPanel', null);
    }

    protected function resourceInspectorForm(): Schema
    {
        $schema = $this->getSchema('form');

        if ($schema === null) {
            throw new \LogicException('Resource inspector form schema is not available.');
        }

        return $schema;
    }

    /**
     * @return class-string
     */
    protected function getInspectorResourceClass(): string
    {
        $sourceResourceClass = $this->configuration()->getSourceResourceClass();

        if ($sourceResourceClass !== null) {
            return $sourceResourceClass;
        }

        if (method_exists(static::class, 'getResource')) {
            return static::getResource();
        }

        throw new \LogicException('Tree index host must define a source resource for the inspector form.');
    }
}
