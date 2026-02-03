<?php

namespace Moox\Prompts\Support;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PromptFlowStateStore
{
    public function __construct(
        protected CacheRepository $cache,
        protected string $prefix = 'moox_prompts_flow:',
        protected int $ttlSeconds = 3600,
    ) {}

    public function create(string $commandName, array $steps): PromptFlowState
    {
        $flowId = (string) Str::uuid();
        $state = new PromptFlowState($flowId, $commandName, $steps, 0, [], []);
        $this->put($state);

        return $state;
    }

    public function get(string $flowId): ?PromptFlowState
    {
        return $this->cache->get($this->key($flowId));
    }

    public function put(PromptFlowState $state): void
    {
        $this->cache->put($this->key($state->flowId), $state, $this->ttlSeconds);
    }

    public function reset(string $flowId): void
    {
        // Get state BEFORE deleting it from cache
        $state = $this->get($flowId);

        // Delete from cache
        $this->cache->forget($this->key($flowId));

        // Mark execution as cancelled if it exists, or create one
        if (class_exists(\Moox\Prompts\Models\CommandExecution::class)) {
            try {
                // Get the current step that was being executed
                $cancelledAtStep = null;
                if ($state) {
                    // The current step is the one that was about to be executed (nextPendingStep)
                    // or if we're in the middle of a step, it's the current one
                    $currentStep = $state->nextPendingStep();
                    if ($currentStep) {
                        $cancelledAtStep = $currentStep;
                    } elseif ($state->currentIndex > 0 && $state->currentIndex <= count($state->steps)) {
                        // If no pending step but we have a currentIndex, we were at this step
                        $cancelledAtStep = $state->steps[$state->currentIndex - 1] ?? null;
                    } elseif ($state->currentIndex > 0) {
                        // Fallback: use the last step in the array
                        $cancelledAtStep = $state->steps[count($state->steps) - 1] ?? null;
                    }
                }

                // Try to update existing record - only if it's not already completed or failed
                $updated = \Moox\Prompts\Models\CommandExecution::where('flow_id', $flowId)
                    ->whereNotIn('status', ['cancelled', 'completed', 'failed'])
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancelled_at_step' => $cancelledAtStep,
                        'step_outputs' => $state->stepOutputs ?? [],
                        'context' => $state->context ?? [],
                    ]);

                // If no record exists yet, create one with cancelled status
                if ($updated === 0 && $state) {
                    $command = app(\Illuminate\Contracts\Console\Kernel::class)->all()[$state->commandName] ?? null;
                    if ($command) {
                        $execution = new \Moox\Prompts\Models\CommandExecution([
                            'flow_id' => $flowId,
                            'command_name' => $state->commandName,
                            'command_description' => $command->getDescription(),
                            'status' => 'cancelled',
                            'started_at' => now(),
                            'cancelled_at' => now(),
                            'cancelled_at_step' => $cancelledAtStep,
                            'steps' => $state->steps ?? [],
                            'step_outputs' => $state->stepOutputs ?? [],
                            'context' => $state->context ?? [],
                        ]);

                        if (\Illuminate\Support\Facades\Auth::check()) {
                            $execution->createdBy()->associate(\Illuminate\Support\Facades\Auth::user());
                        }

                        $execution->save();
                    }
                }
            } catch (\Throwable $e) {
                // Log error for debugging
                Log::error('Failed to mark command execution as cancelled', [
                    'error' => $e->getMessage(),
                    'flow_id' => $flowId,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    protected function key(string $flowId): string
    {
        return $this->prefix.$flowId;
    }
}
