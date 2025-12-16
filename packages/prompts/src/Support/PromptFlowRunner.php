<?php

namespace Moox\Prompts\Support;

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class PromptFlowRunner
{
    public function __construct(
        protected Kernel $artisan,
        protected PromptFlowStateStore $stateStore,
    ) {}

    public function start(string $commandName, array $commandInput): PromptFlowState
    {
        $command = $this->resolveCommand($commandName);
        $steps = ($command instanceof PromptFlowCommand)
            ? $command->promptFlowSteps()
            : ['handle'];

        if (empty($steps)) {
            $steps = ['handle'];
        }

        return $this->stateStore->create($commandName, array_values($steps));
    }

    public function get(string $flowId): ?PromptFlowState
    {
        return $this->stateStore->get($flowId);
    }

    public function runNext(
        PromptFlowState $state,
        array $commandInput,
        PromptResponseStore $responseStore,
    ): array {
        $step = $state->nextPendingStep();

        if ($step === null) {
            return [
                'output' => '',
                'prompt' => null,
                'completed' => true,
                'failed' => false,
                'error' => null,
                'state' => $state,
            ];
        }

        $command = $this->resolveCommand($state->commandName);
        $command->setLaravel(app());

        $input = new ArrayInput($commandInput);
        $output = new BufferedOutput;
        $outputStyle = new OutputStyle($input, $output);
        $command->setOutput($outputStyle);
        $this->setCommandInput($command, $input);

        try {
            app()->instance('moox.prompts.response_store', $responseStore);
            // Make the currently executing step available for the web runtime
            app()->instance('moox.prompts.current_step', $step);

            // restore persisted command properties (e.g., choice) across steps
            $this->restoreCommandContext($command, $state);

            $this->invokeStep($command, $step);

            // persist selected command properties back into state
            $this->captureCommandContext($command, $state);

            $stepOutput = $output->fetch();
            $state->markStepFinished($step, $stepOutput);
            $this->stateStore->put($state);

            return [
                'output' => $stepOutput,
                'prompt' => null,
                'completed' => $state->completed,
                'failed' => false,
                'error' => null,
                'state' => $state,
            ];
        } catch (PendingPromptsException $e) {
            $stepOutput = $output->fetch();
            $this->captureCommandContext($command, $state);
            $this->stateStore->put($state);

            return [
                'output' => $stepOutput,
                'prompt' => $e->getPrompt(),
                'completed' => false,
                'failed' => false,
                'error' => null,
                'state' => $state,
            ];
        } catch (Throwable $e) {
            $stepOutput = $output->fetch();
            $state->markFailed($step, $e->getMessage());
            $this->captureCommandContext($command, $state);
            $this->stateStore->put($state);

            return [
                'output' => $this->appendExceptionToOutput($stepOutput, $e),
                'prompt' => null,
                'completed' => false,
                'failed' => true,
                'error' => $e->getMessage(),
                'state' => $state,
            ];
        }
    }

    protected function appendExceptionToOutput(string $output, Throwable $e): string
    {
        $trace = $e->getTraceAsString();

        return trim($output."\n\n".$this->formatThrowableMessage($e)."\n".$trace);
    }

    protected function formatThrowableMessage(Throwable $e): string
    {
        return sprintf(
            '%s: %s in %s:%d',
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
    }

    protected function resolveCommand(string $commandName)
    {
        $commandInstance = $this->artisan->all()[$commandName] ?? null;

        if (! $commandInstance) {
            throw new \RuntimeException(__('moox-prompts::prompts.errors.command_not_found', ['command' => $commandName]));
        }

        return $commandInstance;
    }

    protected function invokeStep($command, string $method): void
    {
        if (! method_exists($command, $method)) {
            throw new \RuntimeException(__('moox-prompts::prompts.errors.step_not_found', [
                'step' => $method,
                'class' => get_class($command),
            ]));
        }

        $command->{$method}();
    }

    protected function setCommandInput($command, ArrayInput $input): void
    {
        $ref = new \ReflectionClass($command);

        if ($ref->hasProperty('input')) {
            $prop = $ref->getProperty('input');
            $prop->setAccessible(true);
            $prop->setValue($command, $input);
        }
    }

    protected function captureCommandContext($command, PromptFlowState $state): void
    {
        $ref = new \ReflectionObject($command);

        // We persist all non-static properties declared on the concrete command class
        // that contain scalar/array values (e.g. choice, features, projectName, ...).
        foreach ($ref->getProperties() as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            if ($prop->getDeclaringClass()->getName() !== $ref->getName()) {
                // Only properties of the concrete command class, not from the base class
                continue;
            }

            $prop->setAccessible(true);
            $value = $prop->getValue($command);

            if (is_scalar($value) || $value === null || is_array($value)) {
                $state->context[$prop->getName()] = $value;
            }
        }
    }

    protected function restoreCommandContext($command, PromptFlowState $state): void
    {
        if (empty($state->context)) {
            return;
        }

        $ref = new \ReflectionObject($command);
        foreach ($state->context as $propName => $value) {
            if ($ref->hasProperty($propName)) {
                $prop = $ref->getProperty($propName);
                $prop->setAccessible(true);
                $prop->setValue($command, $value);
            }
        }
    }
}
