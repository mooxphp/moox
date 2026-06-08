<?php

declare(strict_types=1);

namespace Moox\Tree\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Moox\Localization\Models\Localization;
use Moox\Tree\Actions\Tree\CreateTreeNodeAction;
use Moox\Tree\Actions\Tree\DeleteTreeNodeAction;
use Moox\Tree\Actions\Tree\MoveTreeNodeAction;
use Moox\Tree\Actions\Tree\UpdateTreeNodeAction;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Config\TreeIndexConfigurationRegistry;
use Moox\Tree\Filament\Pages\TreeIndexCreateInspectorPageFactory;
use Moox\Tree\Support\TreeIndexAuthorizer;
use Moox\Tree\Support\TreeStructure;

class ResourceTreeIndex extends Component
{
    public string $configurationKey = '';

    public ?int $selectedRecordId = null;

    public bool $isCreatingInspector = false;

    public ?int $creatingParentId = null;

    #[Reactive]
    public string $search = '';

    #[Reactive]
    public string $lang = '';

    /** @var array<string, mixed> */
    public array $form = [];

    public function mount(
        string $configurationKey,
        string $search = '',
        string $lang = '',
    ): void {
        $this->configurationKey = $configurationKey;
        $this->authorizeTreeIndex();

        if ($search !== '') {
            $this->search = $search;
        } elseif ($this->search === '' && $this->usesStandaloneToolbarSearch()) {
            $this->search = (string) request()->input('search', request()->input('tableSearch', ''));
        }

        if ($lang !== '') {
            $this->lang = $lang;
        } elseif ($this->lang === '') {
            $this->lang = (string) request()->input('lang', $this->getDefaultLocale());
        }

        $this->syncLangToRequest();
        $this->resetForm();
        $this->loadSelectedRecord();
    }

    public function hydrate(): void
    {
        $this->syncLangToRequest();
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
                : $this->getAncestorIds($this->selectedRecordId),
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

    public function changeLanguage(string $lang): void
    {
        $this->lang = $lang;
        $this->syncLangToRequest();

        $resourceClass = $this->configuration()->getSourceResourceClass();

        if ($resourceClass !== null && method_exists($resourceClass, 'getUrl')) {
            $parameters = ['lang' => $lang];

            if (filled(request()->query('tab'))) {
                $parameters['tab'] = request()->query('tab');
            }

            $this->redirect($resourceClass::getUrl('index', $parameters));
        }
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
    }

    public function selectRecord(int $recordId): void
    {
        $this->authorizeTreeIndex();
        $this->isCreatingInspector = false;
        $this->creatingParentId = null;
        $this->selectedRecordId = $recordId;
        $this->loadSelectedRecord();
    }

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

    public function saveSelectedRecord(): void
    {
        $this->authorizeTreeIndex();

        if ($this->selectedRecordId === null) {
            return;
        }

        $validated = $this->validate($this->validationRules());

        if ($this->hasInvalidParentAssignment((int) $this->selectedRecordId, $validated)) {
            return;
        }

        app(UpdateTreeNodeAction::class, ['configuration' => $this->configuration()])
            ->handle(
                $this->query()->findOrFail($this->selectedRecordId),
                $validated['form'],
            );

        $this->loadSelectedRecord();

        Notification::make()
            ->title('Eintrag gespeichert')
            ->success()
            ->send();
    }

    public function deleteSelectedRecord(): void
    {
        $this->authorizeTreeIndex();

        if ($this->selectedRecordId === null) {
            return;
        }

        app(DeleteTreeNodeAction::class, ['configuration' => $this->configuration()])
            ->handle($this->query()->findOrFail($this->selectedRecordId));

        $nextId = $this->configuration()
            ->applyTreeOrdering($this->query())
            ->value('id');

        $this->selectedRecordId = $nextId === null ? null : (int) $nextId;
        $this->loadSelectedRecord();

        Notification::make()
            ->title('Eintrag gelöscht')
            ->success()
            ->send();
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

        if ($newParentId === $recordKey || ($newParentId !== null && $this->isDescendantOf($newParentId, $recordKey))) {
            Notification::make()
                ->title('Verschieben nicht möglich')
                ->body('Ein Eintrag kann nicht unter sich selbst oder einem eigenen Kind liegen.')
                ->danger()
                ->send();

            return;
        }

        app(MoveTreeNodeAction::class, ['configuration' => $this->configuration()])
            ->handle($record, $newParentId, (int) $position);

        if ($this->selectedRecordId === $recordKey) {
            $this->loadSelectedRecord();
        }
    }

    protected function configuration(): TreeIndexConfiguration
    {
        return TreeIndexConfigurationRegistry::get($this->configurationKey);
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

    private function loadSelectedRecord(): void
    {
        $record = $this->getSelectedRecord();

        if ($record === null) {
            $this->resetForm();

            return;
        }

        $this->hydrateFormFromRecord($record);
    }

    private function hydrateFormFromRecord(Model $record): void
    {
        $configuration = $this->configuration();
        $parentColumn = $configuration->getParentColumn();
        $labelColumn = $configuration->getLabelColumn();

        $this->form = [
            $parentColumn => $this->parentId($record),
            $labelColumn => $this->resolveRecordLabel($record, $labelColumn),
        ];
    }

    private function resolveRecordLabel(Model $record, string $labelColumn): string
    {
        $value = $record->getAttribute($labelColumn);

        if (filled($value)) {
            return (string) $value;
        }

        $fallback = data_get($record, 'display_title');

        if (filled($fallback)) {
            return (string) $fallback;
        }

        return '';
    }

    private function resetForm(): void
    {
        $configuration = $this->configuration();
        $parentColumn = $configuration->getParentColumn();
        $labelColumn = $configuration->getLabelColumn();

        $this->form = [
            $parentColumn => null,
            $labelColumn => '',
        ];
    }

    private function getSelectedRecord(): ?Model
    {
        if ($this->selectedRecordId === null) {
            return null;
        }

        return $this->query()->find($this->selectedRecordId);
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

    /**
     * @return array<string, array<int, string>>
     */
    private function validationRules(): array
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

    /**
     * @param  array<string, mixed>  $validated
     */
    private function hasInvalidParentAssignment(int $recordId, array $validated): bool
    {
        $parentColumn = $this->configuration()->getParentColumn();
        $parentId = $validated['form'][$parentColumn] ?? null;

        if ($parentId === $recordId) {
            $this->addError("form.{$parentColumn}", 'Ein Eintrag kann nicht sein eigener Elterneintrag sein.');

            return true;
        }

        if ($parentId !== null && $this->isDescendantOf((int) $parentId, $recordId)) {
            $this->addError("form.{$parentColumn}", 'Ein Eintrag kann nicht unter einem eigenen Kind verschoben werden.');

            return true;
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function getParentOptions(): array
    {
        $labelColumn = $this->configuration()->getLabelColumn();
        $excludedIds = $this->selectedRecordId === null
            ? []
            : [$this->selectedRecordId, ...$this->getDescendantIds($this->selectedRecordId)];

        $columns = ['id'];

        if ($this->configuration()->isLabelColumnQueryable()) {
            $columns[] = $labelColumn;
        }

        return $this->configuration()
            ->applyTreeOrdering($this->query())
            ->get($columns)
            ->reject(fn (Model $record): bool => in_array((int) $record->getKey(), $excludedIds, true))
            ->mapWithKeys(fn (Model $record): array => [
                (int) $record->getKey() => $this->resolveRecordLabel($record, $labelColumn),
            ])
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function getDescendantIds(int $recordId): array
    {
        $records = $this->query()->get(['id', $this->configuration()->getParentColumn()]);
        $structure = $this->treeStructure();

        return $structure->descendantIds($structure->groupByParent($records), $recordId);
    }

    private function isDescendantOf(int $candidateRecordId, int $parentRecordId): bool
    {
        return in_array($candidateRecordId, $this->getDescendantIds($parentRecordId), true);
    }

    /**
     * @return array<int, int>
     */
    private function getAncestorIds(int $recordId): array
    {
        $parentColumn = $this->configuration()->getParentColumn();
        $ids = [];
        $parentId = $this->query()->whereKey($recordId)->value($parentColumn);

        while ($parentId !== null) {
            $ids[] = (int) $parentId;
            $parentId = $this->query()->whereKey((int) $parentId)->value($parentColumn);
        }

        return $ids;
    }

    private function normalizeParentGroup(int|string|null $parentId): ?int
    {
        if ($parentId === null || $parentId === '' || $parentId === 'root') {
            return null;
        }

        return (int) $parentId;
    }

    private function parentId(Model $record): ?int
    {
        return $this->treeStructure()->parentId($record);
    }

    private function treeStructure(): TreeStructure
    {
        return new TreeStructure($this->configuration());
    }

    private function query(): Builder
    {
        return $this->configuration()->newQuery();
    }

    private function tableName(): string
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $this->configuration()->modelClass();

        return (new $modelClass)->getTable();
    }

    private function shouldApplySearchToTreeQuery(): bool
    {
        $configuration = $this->configuration();

        if ($configuration->usesFilamentTableToolbar()) {
            return true;
        }

        return $this->usesStandaloneToolbarSearch();
    }

    private function usesStandaloneToolbarSearch(): bool
    {
        return $this->configuration()->getSourceResourceClass() === null
            || $this->configuration()->isToolbarSearchEnabled();
    }

    private function syncLangToRequest(): void
    {
        if ($this->lang !== '') {
            request()->merge(['lang' => $this->lang]);
        }
    }

    private function getDefaultLocale(): string
    {
        if (class_exists(Localization::class)) {
            $defaultLocale = Localization::query()
                ->where('is_default', true)
                ->where('is_active_admin', true)
                ->first();

            if ($defaultLocale !== null) {
                return (string) ($defaultLocale->locale_variant ?: $defaultLocale->language?->alpha2 ?: config('app.locale'));
            }
        }

        return (string) config('app.locale');
    }
}
