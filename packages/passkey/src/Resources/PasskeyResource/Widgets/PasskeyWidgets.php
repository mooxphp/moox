<?php

namespace Moox\Passkey\Resources\PasskeyResource\Widgets;

use Override;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Passkey\Models\Passkey;

class PasskeyWidgets extends BaseWidget
{
    #[Override]
    protected function getCards(): array
    {
        $aggregationColumns = [
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(*) as count'),
        ];

        $aggregatedInfo = Passkey::query()
            ->select($aggregationColumns)
            ->first();

        return [
            Stat::make(__('core::passkey.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('core::passkey.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('core::passkey.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
