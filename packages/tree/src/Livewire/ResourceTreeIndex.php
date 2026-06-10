<?php

declare(strict_types=1);

namespace Moox\Tree\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use Moox\Tree\Actions\Tree\CreateTreeNodeAction;
use Moox\Tree\Actions\Tree\MoveTreeNodeAction;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Config\TreeIndexConfigurationRegistry;
use Moox\Tree\Exceptions\InvalidTreeParentException;
use Moox\Tree\Filament\Pages\TreeIndexCreateInspectorPageFactory;
use Moox\Tree\Livewire\Concerns\ManagesTreeForm;
use Moox\Tree\Livewire\Concerns\ManagesTreeSelection;
use Moox\Tree\Livewire\Concerns\ManagesTreeToolbar;
use Moox\Tree\Support\TreeIndexAuthorizer;
use Moox\Tree\Support\TreeStructure;

class ResourceTreeIndex extends Component
{
    use ManagesTreeForm;
    use ManagesTreeSelection;
    use ManagesTreeToolbar;

    public string $configurationKey = '';

    public function mount(
        string $configurationKey,
        string $search = '',
        string $lang = '',
        ?int $selectedRecordId = null,
    ): void {
        $this->configurationKey = $configurationKey;
        $this->authorizeTreeIndex();
        $this->mountTreeToolbar($search, $lang);

        if ($selectedRecordId !== null) {
            $this->selectedRecordId = $selectedRecordId;
        }

        $this->resetForm();
        $this->loadSelectedRecord();
    }

    public function hydrate(): void
    {
        $this->hydrateTreeToolbar();
    }

    public function render(): View
    {
        $tree = $this->getTree();
        $structure = $this->treeStructure();

        return view('filament-tree-index::livewire.resource-tree-index', [
            'configuration' => $this->configuration(),
            'tree' => $tree,
            'treeBranchIdsWithChildren' => $structure->branchIdsWithChildren($tree),
            'treeAncestorIdsForSelection' => $this->selectedRecordId === null
                ? []
                : $structure->ancestorIds($this->selectedRecordId),
            'parentOptions' => $this->getParentOptions(),
            'selectedRecord' => $this->getSelectedRecord(),
            'inspectorPageClass' => $this->configuration()->getInspectorPageClass(),
            'inspectorCreatePageClass' => $this->configuration()->usesResourceCreateInspector()
                ? TreeIndexCreateInspectorPageFactory::resolve($this->configurationKey)
                : null,
            'configurationKey' => $this->configurationKey,
            'isCreatingInspector' => $this->isCreatingInspector,
            'creatingParentId' => $this->creatingParentId,
            'isToolbarSearchEnabled' => $this->configuration()->isToolbarSearchEnabled(),
            'isToolbarLanguageSwitcherEnabled' => $this->configuration()->isToolbarLanguageSwitcherEnabled(),
        ]);
    }

    #[On('tree-index-record-saved')]
    public function refreshAfterInspectorSave(): void
    {
        $this->loadSelectedRecord();
    }

    #[On('tree-index-record-created')]
    public function refreshAfterInspectorCreate(int $recordId): void
    {
        $this->isCreatingInspector = false;
        $this->creatingParentId = null;
        $this->selectedRecordId = $recordId;
        $this->syncTreeSelectionToParent();
    }

    #[On('tree-index-create-root')]
    public function createRootNode(): void
    {
        $this->authorizeTreeIndex();
        $this->createNode();
    }

    public function createChildNode(): void
    {
        $this->authorizeTreeIndex();
        $this->createNode($this->selectedRecordId);
    }

    public function moveTreeNode(int|string $recordId, int|string $position, int|string|null $parentId = null): void
    {
        if (! $this->configuration()->isReorderable()) {
            return;
        }

        $this->authorizeTreeIndex();

        $record = $this->query()->findOrFail((int) $recordId);
        $newParentId = $this->normalizeParentGroup($parentId);
        $recordKey = (int) $record->getKey();

        try {
            app(MoveTreeNodeAction::class, ['configuration' => $this->configuration()])
                ->handle($record, $newParentId, (int) $position);
        } catch (InvalidTreeParentException $exception) {
            Notification::make()
                ->title('Verschieben nicht möglich')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        if ($this->selectedRecordId === $recordKey) {
            $this->loadSelectedRecord();
        }
    }

    protected function configuration(): TreeIndexConfiguration
    {
        return TreeIndexConfigurationRegistry::resolve($this->configurationKey);
    }

    protected function authorizeTreeIndex(): void
    {
        if (! config('filament-tree-index.authorization.enabled', true)) {
            return;
        }

        app(TreeIndexAuthorizer::class, ['configuration' => $this->configuration()])->authorize();
    }

    private function createNode(?int $parentId = null): void
    {
        if ($this->configuration()->usesResourceCreateInspector()) {
            $this->isCreatingInspector = true;
            $this->creatingParentId = $parentId;
            $this->selectedRecordId = null;
            $this->syncTreeSelectionToParent();

            return;
        }

        $record = app(CreateTreeNodeAction::class, ['configuration' => $this->configuration()])
            ->handle($parentId);

        $this->selectedRecordId = (int) $record->getKey();
        $this->loadSelectedRecord();

        Notification::make()
            ->title('Eintrag erstellt')
            ->success()
            ->send();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTree(): array
    {
        $records = $this->loadTreeRecords();
        $structure = $this->treeStructure();

        if ($this->configuration()->usesNestedSet()) {
            return $structure->buildNestedSetTree($records);
        }

        return $structure->buildTree($structure->groupByParent($records));
    }

    /**
     * @return Collection<int, Model>
     */
    private function loadTreeRecords(): Collection
    {
        $configuration = $this->configuration();
        $query = $this->query();
        $query = $configuration->applyLanguage($query, $this->lang);

        if ($this->shouldApplySearchToTreeQuery()) {
            $query = $configuration->applySearch($query, $this->search);
        }

        $query = $configuration->applyTreeOrdering($query);

        if ($configuration->isLabelColumnQueryable() || $configuration->usesNestedSet()) {
            $query->select($configuration->treeSelectColumns());
        }

        return $query->get();
    }

    private function normalizeParentGroup(int|string|null $parentId): ?int
    {
        if ($parentId === null || $parentId === '' || $parentId === 'root') {
            return null;
        }

        return (int) $parentId;
    }

    protected function parentId(Model $record): ?int
    {
        return $this->treeStructure()->parentId($record);
    }

    protected function treeStructure(): TreeStructure
    {
        return new TreeStructure($this->configuration());
    }

    protected function query(): Builder
    {
        return $this->configuration()->newQuery();
    }

    protected function tableName(): string
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $this->configuration()->modelClass();

        return (new $modelClass)->getTable();
    }
}
