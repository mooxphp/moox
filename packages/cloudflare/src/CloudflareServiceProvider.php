<?php

declare(strict_types=1);

namespace Moox\Cloudflare;

use Moox\Cache\Support\CacheTargetRegistry;
use Moox\Cloudflare\Targets\CloudflarePurgeAllTarget;
use Moox\Cloudflare\Targets\CloudflarePurgeFilesTarget;
use Moox\Cloudflare\Targets\CloudflarePurgeHostsTarget;
use Moox\Cloudflare\Targets\CloudflarePurgeTagsTarget;
use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class CloudflareServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('cloudflare')
            ->hasConfigFile()
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(CloudflareClient::class);
        $this->app->singleton(CloudflareCachePlugin::class, fn (): CloudflareCachePlugin => CloudflareCachePlugin::make());
    }

    public function packageBooted(): void
    {
        if (! config('cloudflare.enabled', false)) {
            return;
        }

        $registry = $this->app->make(CacheTargetRegistry::class);

        $registry->registerMany([
            CloudflarePurgeAllTarget::make(),
            CloudflarePurgeFilesTarget::make(),
            CloudflarePurgeTagsTarget::make(),
            CloudflarePurgeHostsTarget::make(),
        ]);
    }
}
