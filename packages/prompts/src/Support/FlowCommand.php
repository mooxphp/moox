<?php

namespace Moox\Prompts\Support;

use Illuminate\Console\Command;

interface PromptFlowCommand
{
    /**
     * Liste der Step-Methoden, die der Flow in Reihenfolge ausführt.
     */
    public function promptFlowSteps(): array;
}

/**
 * Basis-Klasse für Flow-basierte Commands.
 *
 * - CLI: führt alle in promptFlowSteps() definierten Methoden der Reihe nach aus.
 * - Web: der PromptFlowRunner ruft die gleichen Methoden stepweise auf.
 *
 * Concrete Commands müssen nur:
 *   - promptFlowSteps(): array implementieren
 *   - die entsprechenden step*-Methoden bereitstellen.
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
