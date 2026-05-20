<?php

declare(strict_types=1);

namespace Moox\Audit;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class AuditServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('audit')
            ->hasConfigFile()
            ->hasMigrations(['create_activity_log_table']);
    }
}
