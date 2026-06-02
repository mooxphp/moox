<?php

declare(strict_types=1);

namespace Moox\Transform;

use Moox\Core\MooxServiceProvider;
use Moox\Transform\Filament\Providers\TransformPanelProvider;
use Spatie\LaravelPackageTools\Package;

class TransformServiceProvider extends MooxServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../config/transform.php', 'transform');
        $this->mergeConfigFrom(__DIR__.'/../config/transform-definition.php', 'transform-definition');
        $this->mergeConfigFrom(__DIR__.'/../config/transform-record.php', 'transform-record');

        if (config('transform.enable-panel')) {
            $this->app->register(TransformPanelProvider::class);
        }
    }

    public function configureMoox(Package $package): void
    {
        $package
            ->name('transform')
            ->hasConfigFile(['transform', 'transform-definition', 'transform-record'])
            ->hasMigrations([
                'create_transform_definitions_table',
                'create_transform_records_table',
            ])
            ->hasTranslations();
    }
}
