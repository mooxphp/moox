<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Console\Command;
use Moox\Builder\Contexts\AppContext;
use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Contexts\PackageContext;
use Moox\Builder\Contexts\PreviewContext;

abstract class AbstractBuilderCommand extends Command
{
    protected function createContext(string $entityName, ?string $package = null, bool $preview = false): BuildContext
    {
        if ($preview) {
            return new PreviewContext(
                entityName: $entityName,
                basePath: app_path('Builder'),
                baseNamespace: 'App\\Builder'
            );
        }

        if ($package) {
            [$vendor, $name] = $this->parsePackageName($package);
            $paths = config('builder.contexts.package.paths', []);

            return new PackageContext(
                entityName: $entityName,
                basePath: base_path("packages/{$vendor}/{$name}"),
                baseNamespace: "\\{$vendor}\\{$name}",
                paths: $paths
            );
        }

        $paths = config('builder.contexts.app.paths', []);

        return new AppContext(
            entityName: $entityName,
            basePath: app_path(),
            baseNamespace: 'App',
            paths: $paths
        );
    }

    private function parsePackageName(string $package): array
    {
        if (str_contains($package, '/')) {
            return explode('/', $package);
        }

        return [$package, $this->guessPackageName($package)];
    }

    private function guessPackageName(string $vendor): string
    {
        return $vendor.'Package';
    }
}
