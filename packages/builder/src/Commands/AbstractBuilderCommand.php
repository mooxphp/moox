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

        return ContextFactory::create(
            contextType: $contextType,
            entityName: $entityName,
            packageNamespace: $package
        );
    }
}
