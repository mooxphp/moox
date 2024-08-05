<?php

namespace Moox\Core\Traits;

use Filament\Resources\Components\Tab;
use Illuminate\Support\Facades\Config;

trait HasDynamicTabs
{
    public function getDynamicTabs(string $configKey, string $modelClass): array
    {
        $tabsConfig = Config::get($configKey, []);
        $tabs = [];

        foreach ($tabsConfig as $key => $tabConfig) {
            $tab = Tab::make($tabConfig['label'])
                ->icon($tabConfig['icon']);

            $queryConditions = $tabConfig['query'];

            if (empty($queryConditions)) {
                $tab->modifyQueryUsing(fn ($query) => $query)
                    ->badge($modelClass::query()->count());
            } else {
                $tab->modifyQueryUsing(function ($query) use ($queryConditions) {
                    return $this->applyConditions($query, $queryConditions);
                });

                $badgeCountQuery = $modelClass::query();
                $badgeCountQuery = $this->applyConditions($badgeCountQuery, $queryConditions);
                $tab->badge($badgeCountQuery->count());
            }

            $tabs[$key] = $tab;
        }

        return $tabs;
    }

    protected function applyConditions($query, $conditions)
    {
        foreach ($conditions as $condition) {
            $query = $query->where($condition['field'], $condition['operator'], $condition['value']);
        }

        return $query;
    }
}
