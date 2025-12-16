<?php

namespace Moox\Prompts\Support;

use Illuminate\Console\Command;

interface PromptFlowCommand
{
    /**
     * List of step methods that the flow executes in order.
     */
    public function promptFlowSteps(): array;
}

/**
 * Base class for flow-based commands.
 *
 * - CLI: executes all methods defined in promptFlowSteps() sequentially.
 * - Web: the PromptFlowRunner calls the same methods step by step.
 *
 * Concrete commands only need to:
 *   - implement promptFlowSteps(): array
 *   - provide the corresponding step* methods.
 */
abstract class FlowCommand extends Command implements PromptFlowCommand
{
    public function handle(): int
    {
        foreach ($this->promptFlowSteps() as $step) {
            if (method_exists($this, $step)) {
                $this->{$step}();
            }
        }

        return self::SUCCESS;
    }
}
