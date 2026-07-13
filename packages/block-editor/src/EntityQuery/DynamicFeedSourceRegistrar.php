<?php

declare(strict_types=1);

namespace Moox\BlockEditor\EntityQuery;

final class DynamicFeedSourceRegistrar
{
    public static function registerFromConfig(): void
    {
        $sources = config('moox-editor.dynamic_feed.sources', []);

        if (! is_array($sources)) {
            return;
        }

        foreach ($sources as $key => $config) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            if (! is_array($config)) {
                continue;
            }

            if (($config['enabled'] ?? true) === false) {
                continue;
            }

            EntityQuerySourceRegistry::register($key, $config);
        }
    }
}
