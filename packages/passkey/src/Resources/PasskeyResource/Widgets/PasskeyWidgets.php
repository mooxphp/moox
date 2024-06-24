<?php

namespace Moox\Passkey\Resources\PasskeyResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Moox\Passkey\Models\Passkey;

class PasskeyWidgets extends BaseWidget
{
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
            Stat::make(__('passkey::translations.totalone'), $aggregatedInfo->count ?? 0),
            Stat::make(__('passkey::translations.totaltwo'), $aggregatedInfo->count ?? 0),
            Stat::make(__('passkey::translations.totalthree'), $aggregatedInfo->count ?? 0),
        ];
    }
}
