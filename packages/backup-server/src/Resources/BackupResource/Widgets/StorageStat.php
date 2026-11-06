<?php

declare(strict_types=1);

namespace Moox\BackupServerUi\Resources\BackupResource\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;

class StorageStat extends Stat
{
    protected string $view = 'backup-server-ui::widgets.storage-stat';
}
