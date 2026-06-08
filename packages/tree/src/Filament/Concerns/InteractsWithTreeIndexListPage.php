<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Concerns;

use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Config\TreeIndexConfigurationRegistry;
use Moox\Tree\Contracts\ConfiguresTreeIndex;
use Moox\Tree\Support\TreeLocale;
use ReflectionProperty;

trait InteractsWithTreeIndexListPage
{
    public string $lang = '';

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
    }

    public function table(Table $table): Table
    {
        $table = static::getResource()::table($table);

        $table = $this->configureTreeIndexTableToolbar($table);

        return $table->content(fn (): View => view('filament-tree-index::filament.pages.embedded-tree-content', [
            'configurationKey' => $this->treeIndexConfigurationKey !== ''
                ? $this->treeIndexConfigurationKey
                : static::getResource(),
        ]));
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

    public function updatedActiveTab(): void
    {
        $this->afterActiveTabChanged();
    }

    protected function afterActiveTabChanged(): void
    {
        $this->refreshTreeIndexConfiguration();
    }

    public function updatedTableSearch(): void
    {
        $this->refreshTreeIndexConfiguration();
    }

    public function updatedTableFilters(): void
    {
        $this->refreshTreeIndexConfiguration();
    }

    public function changeLanguage(string $lang): void
    {
        $this->lang = $lang;
        TreeLocale::syncToRequest($this->lang);

        $tab = property_exists($this, 'activeTab') && filled($this->activeTab ?? null)
            ? (string) $this->activeTab
            : null;

        $this->redirect(static::getResource()::getUrl(
            'index',
            TreeLocale::languageChangeParameters($lang, $tab),
        ));
    }
}
