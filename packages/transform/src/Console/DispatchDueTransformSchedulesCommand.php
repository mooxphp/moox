<?php

declare(strict_types=1);

namespace Moox\Transform\Console;

use Cron\CronExpression;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Moox\Transform\Contracts\HandlesScheduledTransformDefinitions;
use Moox\Transform\Jobs\RunScheduledTransformDefinitionJob;
use Moox\Transform\Models\TransformDefinition;

final class DispatchDueTransformSchedulesCommand extends Command
{
    protected $signature = 'transform:dispatch-due';

    protected $description = 'Queue transform definitions whose schedule expression is due';

    public function handle(): int
    {
        $now = now();
        $queued = 0;

        $due = TransformDefinition::query()
            ->where('is_active', true)
            ->whereNotNull('schedule')
            ->where('schedule', '!=', '')
            ->get(['id', 'name', 'schedule'])
            ->filter(fn (TransformDefinition $definition): bool => $this->isDue($definition->schedule, $now))
            ->values();

        foreach (app()->tagged(HandlesScheduledTransformDefinitions::class) as $handler) {
            if (! $handler instanceof HandlesScheduledTransformDefinitions) {
                continue;
            }

            $before = $due->count();
            $due = $handler->claim($due)->values();
            $queued += $before - $due->count();
        }

        foreach ($due as $definition) {
            RunScheduledTransformDefinitionJob::dispatch((int) $definition->getKey());
            $queued++;
        }

        $this->info(sprintf('Queued %d transform run(s).', $queued));

        return self::SUCCESS;
    }

    private function isDue(mixed $expression, Carbon $now): bool
    {
        if (! is_string($expression) || trim($expression) === '') {
            return false;
        }

        try {
            return (new CronExpression(trim($expression)))->isDue($now);
        } catch (InvalidArgumentException $exception) {
            $this->warn($exception->getMessage());

            return false;
        }
    }
}
