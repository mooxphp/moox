<?php

declare(strict_types=1);

namespace Moox\Connect\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Moox\Connect\Models\ApiEndpoint;
use Moox\Connect\Traits\ConfiguresConnectQueue;

final class RunConnectionTreeJob implements ShouldQueue
{
    use ConfiguresConnectQueue;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private int $connectionId,
        private int $levelDelaySeconds = 0,
        private ?int $startEndpointId = null,
    ) {
        $this->configureConnectQueue('tree', connectionId: $this->connectionId);
    }

    public function handle(): void
    {
        $treeRunId = (string) Str::uuid();

        $endpoints = ApiEndpoint::query()
            ->where('api_connection_id', $this->connectionId)
            ->whereNull('deleted_at')
            ->get(['id', 'parent_endpoint_id', 'direct_access', 'status']);

        if ($endpoints->isEmpty()) {
            return;
        }

        $byParent = [];
        $nodes = [];
        foreach ($endpoints as $e) {
            $nodes[(int) $e->id] = (int) $e->parent_endpoint_id ?: null;
            $byParent[(int) ($e->parent_endpoint_id ?: 0)][] = (int) $e->id;
        }

        $roots = $byParent[0] ?? [];
        if ($roots === []) {
            // Fallback: wenn alles einen Parent hat (Zyklus/Fehler), nichts tun
            return;
        }

        if ($this->startEndpointId !== null) {
            // Subtree run from one endpoint
            if (! array_key_exists($this->startEndpointId, $nodes)) {
                return;
            }
            $roots = [$this->startEndpointId];
        }

        // Level-order (BFS): Kinder nach Depth sammeln
        $depths = [];
        $queue = [];
        foreach ($roots as $rootId) {
            $depths[$rootId] = 0;
            $queue[] = $rootId;
        }

        while ($queue !== []) {
            $current = array_shift($queue);
            $children = $byParent[$current] ?? [];
            foreach ($children as $childId) {
                // simple cycle protection
                if (array_key_exists($childId, $depths)) {
                    continue;
                }
                $depths[$childId] = ($depths[$current] ?? 0) + 1;
                $queue[] = $childId;
            }
        }

        // Endpoints in Levels gruppieren: level 0 = roots, level 1 = children, ...
        $levels = [];
        foreach ($depths as $endpointId => $depth) {
            $levels[(int) $depth][] = (int) $endpointId;
        }
        ksort($levels);

        // Safe execution: next level starts only after the previous finished successfully.
        // Optional delay between levels (scheduled on the level-job itself).
        $job = RunConnectionTreeLevelJob::dispatch($this->connectionId, $levels, $treeRunId, 0);
        if ($this->levelDelaySeconds > 0) {
            // Delay for first level (rarely needed); subsequent levels can be scheduled by the caller if desired.
            $job->delay(now()->addSeconds($this->levelDelaySeconds));
        }
    }
}
