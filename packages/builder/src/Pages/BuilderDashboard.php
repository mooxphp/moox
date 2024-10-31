<?php

declare(strict_types=1);

namespace Moox\Builder\Pages;

use Filament\Pages\Dashboard;

class BuilderDashboard extends Dashboard
{
    protected static ?string $title = 'Moox Builder';

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?int $navigationSort = -2;
}
