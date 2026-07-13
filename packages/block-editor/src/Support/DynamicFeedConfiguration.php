<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Support;

final class DynamicFeedConfiguration
{
    public static function mergePackageDefaults(): void
    {
        $packageConfigPath = dirname(__DIR__, 2).'/config/moox-editor.php';

        if (! is_file($packageConfigPath)) {
            return;
        }

        /** @var array<string, mixed> $packageConfig */
        $packageConfig = require $packageConfigPath;
        $defaults = $packageConfig['dynamic_feed'] ?? [];

        if (! is_array($defaults)) {
            return;
        }

        $current = config('moox-editor.dynamic_feed', []);

        if (! is_array($current)) {
            $current = [];
        }

        config([
            'moox-editor.dynamic_feed' => array_replace_recursive($defaults, $current),
        ]);
    }
}
