<?php

declare(strict_types=1);

namespace Moox\Cache\Targets;

use Moox\Cache\Contracts\CacheTarget;
use Moox\Cache\Support\ArtisanCacheTarget;

final class BuiltinTargets
{
    /**
     * @return list<CacheTarget>
     */
    public static function all(): array
    {
        return [
            ArtisanCacheTarget::make(
                key: 'application-cache',
                label: __('moox-cache::cache.targets.application_cache.label'),
                command: 'cache:clear',
                description: __('moox-cache::cache.targets.application_cache.description'),
            ),
            ArtisanCacheTarget::make(
                key: 'config-cache',
                label: __('moox-cache::cache.targets.config_cache.label'),
                command: 'config:clear',
                description: __('moox-cache::cache.targets.config_cache.description'),
            ),
            ArtisanCacheTarget::make(
                key: 'route-cache',
                label: __('moox-cache::cache.targets.route_cache.label'),
                command: 'route:clear',
                description: __('moox-cache::cache.targets.route_cache.description'),
            ),
            ArtisanCacheTarget::make(
                key: 'view-cache',
                label: __('moox-cache::cache.targets.view_cache.label'),
                command: 'view:clear',
                description: __('moox-cache::cache.targets.view_cache.description'),
            ),
            ArtisanCacheTarget::make(
                key: 'event-cache',
                label: __('moox-cache::cache.targets.event_cache.label'),
                command: 'event:clear',
                description: __('moox-cache::cache.targets.event_cache.description'),
            ),
            ArtisanCacheTarget::make(
                key: 'compiled',
                label: __('moox-cache::cache.targets.compiled.label'),
                command: 'clear-compiled',
                description: __('moox-cache::cache.targets.compiled.description'),
            ),
            ArtisanCacheTarget::make(
                key: 'optimize-clear',
                label: __('moox-cache::cache.targets.optimize_clear.label'),
                command: 'optimize:clear',
                description: __('moox-cache::cache.targets.optimize_clear.description'),
                icon: 'heroicon-o-bolt',
                color: 'primary',
            ),
            CustomKeyCacheTarget::make(),
            CacheStoreFlushTarget::make(),
        ];
    }

    public static function get(string $key): ?CacheTarget
    {
        foreach (self::all() as $target) {
            if ($target->key() === $key) {
                return $target;
            }
        }

        return null;
    }
}
