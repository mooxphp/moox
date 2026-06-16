<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Concerns;

use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Config\TreeIndexConfigurationRegistry;
use Moox\Tree\Contracts\ConfiguresTreeIndex;
use Moox\Tree\Support\TreeIndexSelection;
use Moox\Tree\Support\TreeLocale;
use ReflectionProperty;

trait InteractsWithTreeIndexListPage
{
    #[Url(as: 'lang', except: '')]
    public string $lang = '';

    #[Url(as: 'selected', except: null)]
    public ?int $treeSelectedId = null;

    public function bootInteractsWithTreeIndexListPage(): void
    {
        $this->syncDefaultListPageTab();

        if (! TreeLocale::isFullPageRequest()) {
            return;
        }

        $missingLang = TreeLocale::missingLangIndexParameters();

        if ($missingLang === null) {
            return;
        }

        $this->redirect(static::getResource()::getUrl('index', $missingLang));
    }

    public function getTable(): Table
    {
        $this->ensureTableIsInitialized();

        return parent::getTable();
    }

    protected function ensureTableIsInitialized(): void
    {
        $tableProperty = new ReflectionProperty($this, 'table');

        if ($tableProperty->isInitialized($this)) {
            return;
        }

        $this->mountInteractsWithTable();
        $this->bootedInteractsWithTable();
    }

    public function mountInteractsWithTreeIndexListPage(): void
    {
        $this->mountInteractsWithTable();

        $this->lang = (string) request()->input('lang', TreeLocale::resolveDefaultLocale());
        TreeLocale::syncToRequest($this->lang);
    }

    public function hydrateInteractsWithTreeIndexListPage(): void
    {
        TreeLocale::syncToRequest($this->lang);
        $this->syncActiveTabToRequest();
    }

    public function table(Table $table): Table
    {
        $table = static::getResource()::table($table);

        $table = $this->configureTreeIndexTableToolbar($table);

        return $table->content(fn (): View => view('filament-tree-index::filament.pages.tree-index-content', $this->getTreeIndexViewData()));
    }

    /**
     * Tree index uses the table toolbar only for tabs, filters, search, and language — not list sorting or row actions.
     */
    protected function configureTreeIndexTableToolbar(Table $table): Table
    {
        $resource = static::getResource();

        if (! is_a($resource, ConfiguresTreeIndex::class, true)) {
            return $table;
        }

        $configuration = $resource::treeIndex();

        if (! $configuration->usesFilamentTableToolbar()) {
            return $table;
        }

        $columns = array_map(
            static function (Column $column): Column {
                if (method_exists($column, 'sortable')) {
                    return $column->sortable(false);
                }

                return $column;
            },
            $table->getColumns(),
        );

        return $table
            ->columns($columns)
            ->defaultSort($configuration->getSortColumn(), 'asc')
            ->recordActions([])
            ->toolbarActions([])
            ->paginated(false);
    }

    protected function applyForwardedListQuery(TreeIndexConfiguration $configuration): TreeIndexConfiguration
    {
        if ($configuration->getSourceResourceClass() === null) {
            return $configuration;
        }

        return $configuration->modifyQuery(function (Builder $query): Builder {
            $query = static::getResource()::getEloquentQuery();

            return $this->applyFiltersToTableQuery($query);
        });
    }

    protected function refreshTreeIndexConfiguration(): void
    {
        $this->syncActiveTabToRequest();

        if ($this->treeIndexConfigurationKey === '') {
            return;
        }

        $resource = static::getResource();

        if (! is_a($resource, ConfiguresTreeIndex::class, true)) {
            return;
        }

        $configuration = $resource::treeIndex();

        if ($configuration->getSourceResourceClass() !== null) {
            $configuration = $this->applyForwardedListQuery($configuration);
        }

        TreeIndexConfigurationRegistry::register($this->treeIndexConfigurationKey, $configuration);
    }

    /**
     * Always runs on tab change — even when consumers override {@see updatedActiveTab()}.
     * Syncs `tab` into the request for {@see getEloquentQuery()} and clears {@see ?selected=}.
     */
    public function updated(mixed $property): void
    {
        if ($property !== 'activeTab') {
            return;
        }

        $this->syncActiveTabToRequest();
        $this->clearTreeSelection();
    }

    protected function clearTreeSelection(): void
    {
        if ($this->treeSelectedId === null) {
            return;
        }

        $this->treeSelectedId = null;
        $this->isCreatingInspector = false;
        $this->creatingParentId = null;
    }

    protected function clearTreeSelectionUnlessVisibleInCurrentQuery(): void
    {
        if (TreeIndexSelection::isVisibleInQuery($this->treeSelectedId, $this->treeIndexListQuery())) {
            return;
        }

        $this->clearTreeSelection();
    }

    protected function treeIndexListQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery();

        return $this->applyFiltersToTableQuery($query);
    }

    public function updatedTableSearch(): void
    {
        $this->refreshTreeIndexConfiguration();
    }

    public function updatedTableFilters(): void
    {
        $this->refreshTreeIndexConfiguration();
    }

    protected function usesListPageTabs(): bool
    {
        return in_array(HasListPageTabs::class, class_uses_recursive(static::class), true);
    }

    protected function defaultListPageTab(): string
    {
        return 'all';
    }

    protected function syncDefaultListPageTab(): void
    {
        if (! $this->usesListPageTabs() || request()->has('tab')) {
            return;
        }

        $defaultTab = $this->defaultListPageTab();

        if (property_exists($this, 'activeTab')) {
            $this->activeTab = $defaultTab;
        }

        TreeLocale::syncTabToRequest($defaultTab);
    }

    protected function syncActiveTabToRequest(): void
    {
        if (! $this->usesListPageTabs()) {
            return;
        }

        if (property_exists($this, 'activeTab') && filled($this->activeTab ?? null)) {
            TreeLocale::syncTabToRequest((string) $this->activeTab);
        }
    }
}
