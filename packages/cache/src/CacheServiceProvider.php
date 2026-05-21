<?php

declare(strict_types=1);

namespace Moox\Cache;

use Moox\Cache\Contracts\CacheTarget;
use Moox\Cache\Plugins\CachePlugin;
use Moox\Cache\Support\CacheTargetRegistry;
use Moox\Cache\Targets\BuiltinTargets;
use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class CacheServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('cache')
            ->hasConfigFile('moox-cache')
            ->hasTranslations()
            ->hasViews();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(CacheTargetRegistry::class);

        $this->app->singleton(CachePlugin::class, fn (): CachePlugin => CachePlugin::make());
    }

    public function packageBooted(): void
    {
        $registry = $this->app->make(CacheTargetRegistry::class);

        $enabled = config('moox-cache.enabled_targets', []);

        foreach ($enabled as $key) {
            $target = BuiltinTargets::get((string) $key);

            if ($target instanceof CacheTarget) {
                $registry->register($target);
            }
        }

        if (! $registry->has('custom-key')) {
            $customKeyTarget = BuiltinTargets::get('custom-key');

            if ($customKeyTarget instanceof CacheTarget) {
                $registry->register($customKeyTarget);
            }
        }

        if (! $registry->has('cache-store-flush')) {
            $flushTarget = BuiltinTargets::get('cache-store-flush');

            if ($flushTarget instanceof CacheTarget) {
                $registry->register($flushTarget);
            }
        }
    }
}
