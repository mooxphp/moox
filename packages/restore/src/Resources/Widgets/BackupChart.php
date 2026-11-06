<?php

declare(strict_types=1);

namespace Moox\Restore\Resources\Widgets;

use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Override;
use Spatie\BackupServer\Models\Backup;

class BackupChart extends ChartWidget
{
    protected ?string $heading = 'Chart';

    protected function getData(): array
    {
        $data = Trend::model(Backup::class)
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->average('size_in_kb');

        return [
            'datasets' => [
                [
                    'label' => 'Backup size (kb)',
                    'data' => $data->map(fn (mixed $value): mixed => $value instanceof TrendValue ? $value->aggregate : null)->all(),
                ],
            ],
            'labels' => $data->map(fn (mixed $value): string => $value instanceof TrendValue ? (string) $value->date : '')->all(),
        ];
    }

    #[Override]
    protected function getType(): string
    {
        return 'line';
    }
}
