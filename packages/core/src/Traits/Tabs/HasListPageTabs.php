<?php

namespace Moox\Core\Traits\Tabs;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Moox\Core\Support\Resources\ScopedResourceContext;
use Moox\Core\Traits\HasQueriesInConfig;

trait HasListPageTabs
{
    use HasQueriesInConfig;

    public function mountTabsInListPage(): void
    {
        static::getResource()::setCurrentTab($this->activeTab);
    }

    public function updatedActiveTab(): void
    {
        static::getResource()::setCurrentTab($this->activeTab);

        if ($this instanceof ListRecords) {
            $this->tableFilters = null;
            $this->tableSort = null;
            $this->resetTable();
            $this->cachedDefaultTableColumnState = null;
            $this->applyTableColumnManager();
        } elseif (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    protected function getTableQuery(): Builder
    {
        if ($this instanceof BaseListDrafts) {
            $this->syncLangToRequest();
        }

        return static::getResource()::getTableQuery($this->activeTab);
    }

    public function getDynamicTabs(string $configKey, string $modelClass): array
    {
        $tabsConfig = Config::get($configKey, []);
        $tabs = [];
        $resource = static::getResource();

        foreach ($tabsConfig as $key => $tabConfig) {
            if (isset($tabConfig['visible']) && ! $tabConfig['visible']) {
                continue;
            }

            $tab = Tab::make($tabConfig['label'])
                ->icon($tabConfig['icon']);

            $queryConditions = $tabConfig['query'];

            if (empty($queryConditions)) {
                $badgeQuery = $modelClass::query();
                $badgeQuery = ScopedResourceContext::applyScope($badgeQuery, $resource);

                $tab->modifyQueryUsing(fn ($query) => $query)
                    ->badge($badgeQuery->count());
            } else {
                $tab->modifyQueryUsing(fn ($query) => $this->applyConditions($query, $queryConditions));

                $badgeCountQuery = $modelClass::query();
                $badgeCountQuery = ScopedResourceContext::applyScope($badgeCountQuery, $resource);
                $badgeCountQuery = $this->applyConditions($badgeCountQuery, $queryConditions);

                $tab->badge($badgeCountQuery->count());
            }

            $tabs[$key] = $tab;
        }

        return $tabs;
    }
}
