<?php

namespace Moox\Core\Traits\Tabs;

use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
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
        $this->tableFilters = null;
        $this->tableSortColumn = null;
        $this->tableSortDirection = null;
        $this->resetTable();
    }

    protected function getTableQuery(): Builder
    {
        return static::getResource()::getTableQuery($this->activeTab);
    }

    public function getDynamicTabs(string $configKey, string $modelClass): array
    {
        $tabsConfig = Config::get($configKey, []);
        $tabs = [];

        foreach ($tabsConfig as $key => $tabConfig) {
            if (isset($tabConfig['visible']) && ! $tabConfig['visible']) {
                continue;
            }

            $tab = Tab::make($tabConfig['label'])
                ->icon($tabConfig['icon']);

            $queryConditions = $tabConfig['query'];

            if (empty($queryConditions)) {
                $tab->modifyQueryUsing(fn ($query) => $query)
                    ->badge($modelClass::query()->count());
            } else {
                $tab->modifyQueryUsing(fn ($query) => $this->applyConditions($query, $queryConditions));

                $badgeCountQuery = $modelClass::query();
                $badgeCountQuery = $this->applyConditions($badgeCountQuery, $queryConditions);

                $tab->badge($badgeCountQuery->count());
            }

            $tabs[$key] = $tab;
        }

        return $tabs;
    }
}
