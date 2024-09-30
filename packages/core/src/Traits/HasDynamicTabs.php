<?php

namespace Moox\Core\Traits;

use Filament\Resources\Components\Tab;
use Illuminate\Support\Facades\Config;

trait HasDynamicTabs
{
    use QueriesInConfig;

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
}
