<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\Tree\Actions\Tree\CreateTreeNodeAction;
use Moox\Tree\Actions\Tree\MoveTreeNodeAction;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Config\TreeIndexConfigurationRegistry;
use Moox\Tree\Exceptions\InvalidTreeParentException;
use Moox\Tree\Support\TreeIndexAuthorizer;
use Moox\Tree\Support\TreeLocale;
use Moox\Tree\Support\TreeStructure;

trait InteractsWithResourceTreeIndex
{
    use InteractsWithTreeResourceInspectorForm;
    use ManagesTreeForm;
    use ManagesTreeSelection;

    public string $search = '';

    public function mountInteractsWithResourceTreeIndex(): void
    {
        if ($this->search === '' && $this->usesStandaloneToolbarSearch()) {
            $this->search = (string) request()->input('search', request()->input('tableSearch', ''));
        }

        $this->loadInspectorOrStubForm();
    }

    public function hydrateInteractsWithResourceTreeIndex(): void
    {
        TreeLocale::syncToRequest($this->lang);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTreeIndexViewData(): array
    {
        $tree = $this->getTree();
        $structure = $this->treeStructure();
        $configuration = $this->configuration();

        return [
            'configuration' => $configuration,
            'tree' => $tree,
            'treeBranchIdsWithChildren' => $structure->branchIdsWithChildren($tree),
            'treeAncestorIdsForSelection' => $this->treeSelectedId === null
                ? []
                : $structure->ancestorIds($this->treeSelectedId),
            'parentOptions' => $this->getParentOptions(),
            'selectedRecord' => $this->getSelectedRecord(),
            'usesResourceInspectorPanel' => $this->usesResourceInspectorPanel(),
            'usesResourceCreateInspector' => $configuration->usesResourceCreateInspector(),
            'isCreatingInspector' => $this->isCreatingInspector,
            'creatingParentId' => $this->creatingParentId,
            'isToolbarSearchEnabled' => $configuration->isToolbarSearchEnabled(),
            'isToolbarLanguageSwitcherEnabled' => $configuration->isToolbarLanguageSwitcherEnabled(),
            'canRenderInspectorForm' => $this->supportsInspectorForm(),
        ];
    }

    public function changeLanguage(string $lang): void
    {
        $this->lang = $lang;
        TreeLocale::syncToRequest($this->lang);

        if ($this->treeSelectedId !== null || $this->isCreatingInspector) {
            $this->refreshInspectorFormForLanguageChange();

            return;
        }

        $this->redirectAfterTreeLanguageChange($lang);
    }

    public function createRootNode(): void
    {
        $this->authorizeTreeIndex();
        $this->createNode();
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

        if ($this->treeSelectedId === $recordKey) {
            $this->loadInspectorOrStubForm();
        }
    }

    protected function treeSearch(): string
    {
        if ($this->configuration()->usesFilamentTableToolbar()) {
            return (string) ($this->tableSearch ?? '');
        }

        return $this->search;
    }

    protected function configuration(): TreeIndexConfiguration
    {
        return TreeIndexConfigurationRegistry::resolve($this->treeIndexConfigurationKey);
    }

    protected function authorizeTreeIndex(): void
    {
        if (! config('filament-tree-index.authorization.enabled', true)) {
            return;
        }

        app(TreeIndexAuthorizer::class, ['configuration' => $this->configuration()])->authorize();
    }

    protected function createNode(): void
    {
        if ($this->configuration()->usesResourceCreateInspector()) {
            $this->isCreatingInspector = true;
            $this->creatingParentId = null;
            $this->treeSelectedId = null;
            $this->fillInspectorFormForCreate();

            return;
        }

        $record = app(CreateTreeNodeAction::class, ['configuration' => $this->configuration()])
            ->handle();

        $this->treeSelectedId = (int) $record->getKey();
        $this->loadInspectorOrStubForm();

        Notification::make()
            ->title('Eintrag erstellt')
            ->success()
            ->send();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getTree(): array
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
    protected function loadTreeRecords(): Collection
    {
        $configuration = $this->configuration();
        $query = $this->query();
        $query = $configuration->applyLanguage($query, $this->lang);

        if ($this->shouldApplySearchToTreeQuery()) {
            $query = $configuration->applySearch($query, $this->treeSearch());
        }

        $query = $configuration->applyTreeOrdering($query);

        if ($configuration->isLabelColumnQueryable() || $configuration->usesNestedSet()) {
            $query->select($configuration->treeSelectColumns());
        }

        return $query->get();
    }

    protected function shouldApplySearchToTreeQuery(): bool
    {
        $configuration = $this->configuration();

        if ($configuration->usesFilamentTableToolbar()) {
            return true;
        }

        return $this->usesStandaloneToolbarSearch();
    }

    protected function usesStandaloneToolbarSearch(): bool
    {
        return $this->configuration()->getSourceResourceClass() === null
            || $this->configuration()->isToolbarSearchEnabled();
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

    protected function refreshInspectorFormForLanguageChange(): void
    {
        if (! $this->usesResourceInspectorPanel()) {
            return;
        }

        if ($this->isCreatingInspector) {
            $this->fillInspectorFormForCreate($this->creatingParentId);

            return;
        }

        if ($this->treeSelectedId !== null) {
            $this->fillInspectorFormForSelectedRecord();
        }
    }

    protected function redirectAfterTreeLanguageChange(string $lang): void
    {
        if (! method_exists(static::class, 'getResource')) {
            return;
        }

        $tab = property_exists($this, 'activeTab') && filled($this->activeTab ?? null)
            ? (string) $this->activeTab
            : null;

        $this->redirect(static::getResource()::getUrl(
            'index',
            TreeLocale::languageChangeParameters($lang, $tab, $this->treeSelectedId),
        ));
    }
}
