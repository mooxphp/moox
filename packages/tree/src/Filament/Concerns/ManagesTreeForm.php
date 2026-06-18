<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Moox\Tree\Actions\Tree\DeleteTreeNodeAction;
use Moox\Tree\Actions\Tree\UpdateTreeNodeAction;
use Moox\Tree\Exceptions\InvalidTreeParentException;
use Moox\Tree\Support\TreeNodeLabelResolver;

trait ManagesTreeForm
{
    /** @var array<string, mixed> */
    public array $form = [];

    public function saveSelectedRecord(): void
    {
        $this->authorizeTreeIndex();

        if ($this->treeSelectedId === null) {
            return;
        }

        $validated = $this->validate($this->validationRules());

        try {
            app(UpdateTreeNodeAction::class, ['configuration' => $this->configuration()])
                ->handle(
                    $this->query()->findOrFail($this->treeSelectedId),
                    $validated['form'],
                );
        } catch (InvalidTreeParentException $exception) {
            $parentColumn = $this->configuration()->getParentColumn();
            $this->addError("form.{$parentColumn}", $exception->getMessage());

            return;
        }

        $this->loadInspectorOrStubForm();

        Notification::make()
            ->title('Eintrag gespeichert')
            ->success()
            ->send();
    }

    public function deleteSelectedRecord(): void
    {
        $this->authorizeTreeIndex();

        if ($this->treeSelectedId === null) {
            return;
        }

        app(DeleteTreeNodeAction::class)
            ->handle($this->query()->findOrFail($this->treeSelectedId));

        $nextId = $this->configuration()
            ->applyTreeOrdering($this->query())
            ->value('id');

        $this->treeSelectedId = $nextId === null ? null : (int) $nextId;
        $this->loadInspectorOrStubForm();

        Notification::make()
            ->title('Eintrag gelöscht')
            ->success()
            ->send();
    }

    protected function loadInspectorOrStubForm(): void
    {
        if ($this->usesResourceInspectorPanel()) {
            $this->fillInspectorFormForSelectedRecord();

            return;
        }

        $this->loadSelectedRecord();
    }

    protected function loadSelectedRecord(): void
    {
        $record = $this->getSelectedRecord();

        if ($record === null) {
            $this->resetForm();

            return;
        }

        $this->hydrateFormFromRecord($record);
    }

    protected function hydrateFormFromRecord(Model $record): void
    {
        $configuration = $this->configuration();
        $parentColumn = $configuration->getParentColumn();
        $labelColumn = $configuration->getLabelColumn();

        $this->form = [
            $parentColumn => $this->parentId($record),
            $labelColumn => TreeNodeLabelResolver::resolve($record, $configuration),
        ];
    }

    protected function resetForm(): void
    {
        $configuration = $this->configuration();
        $parentColumn = $configuration->getParentColumn();
        $labelColumn = $configuration->getLabelColumn();

        $this->form = [
            $parentColumn => null,
            $labelColumn => '',
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function validationRules(): array
    {
        $configuration = $this->configuration();
        $parentColumn = $configuration->getParentColumn();
        $labelColumn = $configuration->getLabelColumn();
        $table = $this->tableName();

        $this->form[$parentColumn] = blank($this->form[$parentColumn] ?? null)
            ? null
            : (int) $this->form[$parentColumn];

        return [
            "form.{$parentColumn}" => ['nullable', 'integer', "exists:{$table},id"],
            "form.{$labelColumn}" => ['required', 'string', 'max:255'],
        ];
    }
}
