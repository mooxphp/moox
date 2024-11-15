<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

class DeleteEntityCommand extends AbstractBuilderCommand
{
    protected $signature = 'builder:delete-entity {name} {--force} {--package=} {--app}';

    protected $description = 'Delete an entity and its files';

    public function __construct()
    {
        parent::__construct();

        // not implemented yet
    }
}
