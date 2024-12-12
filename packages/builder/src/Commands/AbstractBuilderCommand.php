<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Console\Command;
use Moox\Builder\Contexts\BuildContext;
use Moox\Builder\Contexts\ContextFactory;

abstract class AbstractBuilderCommand extends Command
{
    protected function createContext(string $entityName, ?string $package = null, bool $preview = false): BuildContext
    {
        $contextType = match (true) {
            $preview => 'preview',
            $package !== null => 'package',
            default => 'app'
        };

        $config = [];
        if ($package !== null) {
            $config['package_namespace'] = $package;
        }

        return ContextFactory::create(
            $contextType,
            $entityName,
            $config
        );
    }

    protected function getBuildContext(bool $preview, ?string $package = null): string
    {
        return match (true) {
            $preview => 'preview',
            $package !== null => 'package',
            default => 'app'
        };
    }
}
