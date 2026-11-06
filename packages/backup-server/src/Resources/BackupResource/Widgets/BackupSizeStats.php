<?php

declare(strict_types=1);

namespace Moox\BackupServerUi\Resources\BackupResource\Widgets;

use Filament\Support\Enums\IconPosition;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Moox\BackupServerUi\Resources\BackupResource\Pages\ListBackups;
use Moox\BackupServerUi\Support\BackupSizeFormatter;
use Override;
use Spatie\BackupServer\Models\Backup;

class BackupSizeStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    private const int KilobytesPerGigabyte = 1_048_576;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    #[Override]
    protected function getHeading(): ?string
    {
        return __('backup-server-ui::translations.storage_overview');
    }

    #[Override]
    protected function getDescription(): ?string
    {
        $planGb = $this->maxStoragePlanGb();

        if ($planGb !== null) {
            return __('backup-server-ui::translations.storage_overview_plan', [
                'plan' => BackupSizeFormatter::format($planGb * self::KilobytesPerGigabyte),
            ]);
        }

        return $this->hasActiveTableConstraints()
            ? __('backup-server-ui::translations.storage_overview_filtered')
            : __('backup-server-ui::translations.storage_overview_all');
    }

    #[Override]
    protected function getColumns(): int|array|null
    {
        return $this->maxStoragePlanInKb() === null ? 2 : 3;
    }

    protected function getTablePage(): string
    {
        return ListBackups::class;
    }

    /**
     * @return array<Stat>
     */
    #[Override]
    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $sizeInKb = (int) $query->clone()->sum('size_in_kb');
        $realSizeInKb = (int) $query->clone()->sum('real_size_in_kb');
        $backupCount = (int) $query->clone()->count();
        $planInKb = $this->maxStoragePlanInKb();

        $stats = [
            StorageStat::make(
                __('backup-server-ui::translations.size'),
                BackupSizeFormatter::format($sizeInKb),
            )
                ->icon('heroicon-o-circle-stack')
                ->color('primary')
                ->description($this->backupCountDescription($backupCount))
                ->descriptionIcon('heroicon-m-queue-list', IconPosition::Before)
                ->descriptionColor('primary'),
            StorageStat::make(
                __('backup-server-ui::translations.real_size'),
                BackupSizeFormatter::format($realSizeInKb),
            )
                ->icon('heroicon-o-server')
                ->color('warning')
                ->description(__('backup-server-ui::translations.real_size_hint'))
                ->descriptionIcon('heroicon-m-circle-stack', IconPosition::Before)
                ->descriptionColor('warning'),
        ];

        if ($planInKb !== null) {
            $stats[] = $this->freeStorageStat($planInKb);
        }

        return $stats;
    }

    private function freeStorageStat(int $planInKb): Stat
    {
        $totalRealSizeInKb = (int) Backup::query()->sum('real_size_in_kb');
        $freeInKb = max(0, $planInKb - $totalRealSizeInKb);
        $usedPercent = (int) min(100, round(($totalRealSizeInKb / $planInKb) * 100));
        $isExhausted = $totalRealSizeInKb >= $planInKb;

        return StorageStat::make(
            __('backup-server-ui::translations.free_storage'),
            BackupSizeFormatter::format($freeInKb),
        )
            ->icon('heroicon-o-inbox')
            ->color($isExhausted ? 'danger' : 'success')
            ->description(__('backup-server-ui::translations.free_storage_description', [
                'plan' => BackupSizeFormatter::format($planInKb),
                'used' => $usedPercent,
            ]))
            ->descriptionIcon(
                $isExhausted ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-badge',
                IconPosition::Before,
            )
            ->descriptionColor($isExhausted ? 'danger' : 'success');
    }

    private function backupCountDescription(int $backupCount): string
    {
        $key = $this->hasActiveTableConstraints()
            ? 'backup-server-ui::translations.backups_filtered'
            : 'backup-server-ui::translations.backups_count';

        return __($key, ['count' => $backupCount]);
    }

    private function maxStoragePlanInKb(): ?int
    {
        $planGb = $this->maxStoragePlanGb();

        if ($planGb === null) {
            return null;
        }

        return $planGb * self::KilobytesPerGigabyte;
    }

    private function maxStoragePlanGb(): ?int
    {
        $value = config('backup-server-ui.max_storage_plan_gb');

        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $planGb = (int) $value;

        return $planGb > 0 ? $planGb : null;
    }

    private function hasActiveTableConstraints(): bool
    {
        if (filled($this->tableSearch)) {
            return true;
        }

        if ($this->tableFilters === null) {
            return false;
        }

        foreach ($this->tableFilters as $filter) {
            if (! is_array($filter)) {
                if (filled($filter)) {
                    return true;
                }

                continue;
            }

            foreach ($filter as $value) {
                if (blank($value)) {
                    continue;
                }

                if (is_array($value) && $value === []) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }
}
