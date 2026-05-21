<?php

declare(strict_types=1);

namespace Moox\CacheStatic;

use Moox\Cache\Support\CacheTargetRegistry;
use Moox\CacheStatic\Targets\PageCacheClearAllTarget;
use Moox\CacheStatic\Targets\PageCacheClearSlugTarget;
use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class CacheStaticServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('cache-static')
            ->hasConfigFile()
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        if (! config('cache-static.enabled', true)) {
            return;
        }

        $registry = $this->app->make(CacheTargetRegistry::class);

        $registry->register(PageCacheClearAllTarget::make());
        $registry->register(PageCacheClearSlugTarget::make());
    }
}
