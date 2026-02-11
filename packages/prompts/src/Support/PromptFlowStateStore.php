<?php

namespace Moox\Prompts\Support;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\DB;
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

    public function reset(string $flowId, ?string $commandName = null): void
    {
        // Get state BEFORE deleting it from cache
        $state = $this->get($flowId);

        // Delete from cache
        $this->cache->forget($this->key($flowId));

        // Mark execution as cancelled if it exists, or create one
        if (! class_exists(\Moox\Prompts\Models\CommandExecution::class)) {
            return;
        }

        try {
            $stepOutputs = $state !== null ? $state->stepOutputs : [];
            $context = $state !== null ? $state->context : [];
            $steps = $state !== null ? $state->steps : [];

            // Reorder step_outputs to match the execution order (steps array order)
            $orderedStepOutputs = [];
            if ($state && ! empty($steps)) {
                foreach ($steps as $step) {
                    if (isset($stepOutputs[$step])) {
                        $orderedStepOutputs[$step] = $stepOutputs[$step];
                    }
                }
                // Add any remaining steps that might not be in the steps array
                foreach ($stepOutputs as $step => $output) {
                    if (! isset($orderedStepOutputs[$step])) {
                        $orderedStepOutputs[$step] = $output;
                    }
                }
            } else {
                $orderedStepOutputs = $stepOutputs;
            }

            // Current step that was being executed
            $cancelledAtStep = null;
            if ($state) {
                $currentStep = $state->nextPendingStep();
                if ($currentStep) {
                    $cancelledAtStep = $currentStep;
                } elseif ($state->currentIndex > 0 && $state->currentIndex <= count($state->steps)) {
                    $cancelledAtStep = $state->steps[$state->currentIndex - 1] ?? null;
                } elseif ($state->currentIndex > 0) {
                    $cancelledAtStep = $state->steps[count($state->steps) - 1] ?? null;
                }
            }

            // Try to update existing record
            $updated = \Moox\Prompts\Models\CommandExecution::query()->where('flow_id', $flowId)
                ->whereNotIn('status', ['cancelled', 'completed', 'failed'])
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_at_step' => $cancelledAtStep,
                    'step_outputs' => $orderedStepOutputs,
                    'context' => $context,
                ]);

            // If no record exists, create one (always when we have state or commandName from component)
            if ($updated === 0) {
                $name = $state !== null ? $state->commandName : $commandName;
                if ($name === null || $name === '') {
                    return;
                }

                $command = app(\Illuminate\Contracts\Console\Kernel::class)->all()[$name] ?? null;
                $description = $command ? $command->getDescription() : $name;

                // Insert directly into database to bypass model casts
                $userId = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : null;
                $userType = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user()::class : null;

                $now = now();
                DB::table('command_executions')->insertGetId([
                    'flow_id' => $flowId,
                    'command_name' => $name,
                    'command_description' => $description,
                    'status' => 'cancelled',
                    'started_at' => $now,
                    'cancelled_at' => $now,
                    'cancelled_at_step' => $cancelledAtStep,
                    'steps' => json_encode($steps),
                    'step_outputs' => json_encode($orderedStepOutputs),
                    'context' => json_encode($context),
                    'created_by_id' => $userId,
                    'created_by_type' => $userType,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to mark command execution as cancelled', [
                'error' => $e->getMessage(),
                'flow_id' => $flowId,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function key(string $flowId): string
    {
        return $this->prefix.$flowId;
    }
}
