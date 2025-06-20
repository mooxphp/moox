<?php

namespace Moox\LoginLink\Resources\LoginLinkResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\LoginLink\Models\LoginLink;
use Override;

class LoginLinkWidgets extends BaseWidget
{
    #[Override]
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = LoginLink::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('core::login-link.login_links_pending'), $aggregatedInfo->count ?? 0),
            Stat::make(__('core::login-link.login_links_used'), $aggregatedInfo->count ?? 0),
            Stat::make(__('core::login-link.login_links_expired'), $aggregatedInfo->count ?? 0),
        ];
    }
}
