<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Commands;

use Illuminate\Console\Command;
use Moox\Builder\Builder\Contexts\AppBuildContext;
use Moox\Builder\Builder\Contexts\BuildContext;
use Moox\Builder\Builder\Contexts\PackageBuildContext;
use Moox\Builder\Builder\Contexts\PreviewBuildContext;

abstract class AbstractBuilderCommand extends Command
{
    protected function createContext(string $entityName, ?string $package = null, bool $preview = false): BuildContext
    {
        if ($preview) {
            return new PreviewBuildContext($entityName);
        }

        if ($package) {
            [$vendor, $name] = $this->parsePackageName($package);

            return new PackageBuildContext(
                entityName: $entityName,
                basePath: base_path("packages/{$vendor}/{$name}"),
                baseNamespace: "\\{$vendor}\\{$name}"
            );
        }

        return new AppBuildContext(
            entityName: $entityName,
            basePath: app_path(),
            baseNamespace: 'App'
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
