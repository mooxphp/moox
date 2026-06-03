<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Concerns;

use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Moox\Localization\Models\Localization;
use Moox\Tree\Config\TreeIndexConfiguration;
use Moox\Tree\Config\TreeIndexConfigurationRegistry;
use Moox\Tree\Contracts\ConfiguresTreeIndex;
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

        $defaultLocalization = Localization::where('is_default', true)->first();
        $defaultLang = $defaultLocalization->locale_variant ?? config('app.locale');

        $this->lang = (string) request()->input('lang', $defaultLang);
        $this->syncLangToRequest();
    }

    public function hydrateInteractsWithTreeIndexListPage(): void
    {
        $this->syncLangToRequest();
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
        $this->syncLangToRequest();

        $parameters = ['lang' => $lang];

        if (property_exists($this, 'activeTab') && filled($this->activeTab ?? null)) {
            $parameters['tab'] = $this->activeTab;
        }

        $this->redirect(static::getResource()::getUrl('index', $parameters));
    }

    protected function syncLangToRequest(): void
    {
        if ($this->lang !== '') {
            request()->merge(['lang' => $this->lang]);
        }
    }
}
