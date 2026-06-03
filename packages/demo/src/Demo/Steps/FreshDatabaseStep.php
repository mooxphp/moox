<?php

declare(strict_types=1);

namespace Moox\Demo\Demo\Steps;

use Illuminate\Console\Command;
use Moox\Demo\Console\DemoConsole;
use Moox\Demo\Demo\DemoContext;

final class FreshDatabaseStep
{
    public function __construct(
        private readonly Command $command,
        private readonly DemoConsole $console,
    ) {}

    public function run(DemoContext $context): void
    {
        if (! $context->fresh) {
            $this->console->skip('migrate:fresh', 'not requested');

            return;
        }

        if (! $this->command->option('no-interaction')) {
            if (! $this->command->confirm('This will run migrate:fresh and erase all data. Continue?', false)) {
                $this->console->skip('migrate:fresh', 'cancelled');

                return;
            }
        }

        $this->console->beginNestedOutput('migrate:fresh');
        $this->command->call('migrate:fresh', ['--force' => true]);
        $this->console->finishTask('migrate:fresh');
    }
}
