<?php

namespace Moox\Builder\Commands;

use Illuminate\Console\Command;

class BuildTestEntityCommand extends Command
{
    protected string $entityName;

    protected string $entityPluralName;

    protected string $entityNamespace;

    protected string $entityPath;

    protected string $entityId;

    protected array $blocks;

    protected array $features;

    protected string $entityLocation;

    // do a GenerateEntity

    // do a PreviewEntity
}
