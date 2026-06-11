<?php

namespace Moox\Prompts\Commands;

use Moox\Prompts\Support\FlowCommand;
use RuntimeException;

use function Moox\Prompts\text;

/**
 * Demo command: verifies failed status handling in Command Executions.
 */
class TestFailedCommand extends FlowCommand
{
    protected $signature = 'prompts:test-failed';

    protected $description = '[Demo] Intentionally fails to test failed status handling';

    public ?string $userName = null;

    public function promptFlowSteps(): array
    {
        return [
            'stepIntro',
            'stepName',
            'stepFail',
        ];
    }

    public function stepIntro(): void
    {
        $this->info('=== Demo: Test Failed Command ===');
        $this->warn('Nur für Entwicklung — dieser Command bricht absichtlich mit einer Exception ab.');
    }

    public function stepName(): void
    {
        $this->userName = text(
            label: 'What is your name?',
            placeholder: 'Enter your name',
            validate: 'required|min:2',
            required: true,
        );

        $this->info("✅ Name: {$this->userName}");
    }

    public function stepFail(): void
    {
        $this->info('About to throw an exception...');

        throw new RuntimeException('Demo exception to verify failed status handling.');
    }
}
