<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Moox\Connect\Jobs\RunEndpointForItemJob;
use Throwable;

final class QueueJobStatsService
{
    /**
     * @return array{queue_driver:string,queue:string,total:int,by_type:array<int,array{name:string,count:int}>,sampled:bool,sample_size:int}
     */
    public function summarizeQueuedJobsByType(): array
    {
        $driver = (string) config('queue.default', 'unknown');

        if ($driver === 'redis') {
            return $this->summarizeQueuedJobsByTypeFromRedis($driver);
        }

        return $this->summarizeQueuedJobsByTypeFromDatabase($driver);
    }

    /**
     * @return array{queue:string,total:int,by_endpoint:array<int,int>}
     */
    public function summarizeRunEndpointForItemJobsByEndpoint(): array
    {
        $driver = (string) config('queue.default', 'unknown');

        if ($driver === 'redis') {
            return $this->summarizeRunEndpointForItemJobsByEndpointFromRedis();
        }

        return $this->summarizeRunEndpointForItemJobsByEndpointFromDatabase();
    }

    /**
     * @return array{queue_driver:string,queue:string,total:int,by_type:array<int,array{name:string,count:int}>,sampled:bool,sample_size:int}
     */
    private function summarizeQueuedJobsByTypeFromDatabase(string $driver): array
    {
        $queueName = (string) config('queue.connections.database.queue', 'default');

        try {
            $rows = DB::table('jobs')->select(['payload', 'queue'])->get();
        } catch (Throwable) {
            return [
                'queue_driver' => $driver,
                'queue' => $queueName,
                'total' => 0,
                'by_type' => [],
                'sampled' => false,
                'sample_size' => 0,
            ];
        }

        $counts = [];
        foreach ($rows as $row) {
            $payload = json_decode((string) ($row->payload ?? ''), true);
            if (! is_array($payload)) {
                continue;
            }

            $shortName = $this->extractShortDisplayName($payload);
            if ($shortName === null) {
                continue;
            }

            $counts[$shortName] = ($counts[$shortName] ?? 0) + 1;
            $queueName = is_string($row->queue) && $row->queue !== '' ? $row->queue : $queueName;
        }

        arsort($counts);

        return [
            'queue_driver' => $driver,
            'queue' => $queueName,
            'total' => array_sum($counts),
            'by_type' => collect($counts)
                ->map(fn (int $count, string $name): array => ['name' => $name, 'count' => $count])
                ->values()
                ->all(),
            'sampled' => false,
            'sample_size' => 0,
        ];
    }

    /**
     * @return array{queue_driver:string,queue:string,total:int,by_type:array<int,array{name:string,count:int}>,sampled:bool,sample_size:int}
     */
    private function summarizeQueuedJobsByTypeFromRedis(string $driver): array
    {
        $connection = (string) config('queue.connections.redis.connection', 'default');
        $queueName = (string) config('queue.connections.redis.queue', 'default');
        $key = 'queues:'.$queueName;

        try {
            $redis = Redis::connection($connection);
            $total = (int) $redis->llen($key);
            if ($total <= 0) {
                return [
                    'queue_driver' => $driver,
                    'queue' => $queueName,
                    'total' => 0,
                    'by_type' => [],
                    'sampled' => false,
                    'sample_size' => 0,
                ];
            }

            $sampleSize = min($total, 500);
            $payloads = $redis->lrange($key, 0, max(0, $sampleSize - 1));

            $counts = [];
            foreach ((array) $payloads as $rawPayload) {
                $payload = json_decode((string) $rawPayload, true);
                if (! is_array($payload)) {
                    continue;
                }

                $shortName = $this->extractShortDisplayName($payload);
                if ($shortName === null) {
                    continue;
                }

                $counts[$shortName] = ($counts[$shortName] ?? 0) + 1;
            }

            arsort($counts);

            return [
                'queue_driver' => $driver,
                'queue' => $queueName,
                'total' => $total,
                'by_type' => collect($counts)
                    ->map(fn (int $count, string $name): array => ['name' => $name, 'count' => $count])
                    ->values()
                    ->all(),
                'sampled' => $sampleSize < $total,
                'sample_size' => $sampleSize,
            ];
        } catch (Throwable) {
            return [
                'queue_driver' => $driver,
                'queue' => $queueName,
                'total' => 0,
                'by_type' => [],
                'sampled' => false,
                'sample_size' => 0,
            ];
        }
    }

    /**
     * @return array{queue:string,total:int,by_endpoint:array<int,int>}
     */
    private function summarizeRunEndpointForItemJobsByEndpointFromDatabase(): array
    {
        $byEndpoint = [];
        $total = 0;
        $queueName = (string) config('queue.connections.database.queue', 'default');

        try {
            $rows = DB::table('jobs')->select(['payload', 'queue'])->get();
        } catch (Throwable) {
            return ['queue' => $queueName, 'total' => 0, 'by_endpoint' => []];
        }

        foreach ($rows as $row) {
            $payload = json_decode((string) ($row->payload ?? ''), true);
            $endpointId = $this->extractRunEndpointForItemJobEndpointIdFromPayload($payload);
            if ($endpointId === null) {
                continue;
            }

            $total++;
            $byEndpoint[$endpointId] = ($byEndpoint[$endpointId] ?? 0) + 1;
            $queueName = is_string($row->queue) && $row->queue !== '' ? $row->queue : $queueName;
        }

        return ['queue' => $queueName, 'total' => $total, 'by_endpoint' => $byEndpoint];
    }

    /**
     * @return array{queue:string,total:int,by_endpoint:array<int,int>}
     */
    private function summarizeRunEndpointForItemJobsByEndpointFromRedis(): array
    {
        $byEndpoint = [];
        $total = 0;
        $connection = (string) config('queue.connections.redis.connection', 'default');
        $queueName = (string) config('queue.connections.redis.queue', 'default');
        $key = 'queues:'.$queueName;

        try {
            $redis = Redis::connection($connection);
            $size = (int) $redis->llen($key);
            if ($size <= 0) {
                return ['queue' => $queueName, 'total' => 0, 'by_endpoint' => []];
            }

            $payloads = $redis->lrange($key, 0, max(0, $size - 1));
            foreach ((array) $payloads as $rawPayload) {
                $payload = json_decode((string) $rawPayload, true);
                $endpointId = $this->extractRunEndpointForItemJobEndpointIdFromPayload($payload);
                if ($endpointId === null) {
                    continue;
                }

                $total++;
                $byEndpoint[$endpointId] = ($byEndpoint[$endpointId] ?? 0) + 1;
            }
        } catch (Throwable) {
            return ['queue' => $queueName, 'total' => 0, 'by_endpoint' => []];
        }

        return ['queue' => $queueName, 'total' => $total, 'by_endpoint' => $byEndpoint];
    }

    private function extractShortDisplayName(array $payload): ?string
    {
        $displayName = $payload['displayName'] ?? null;
        if (! is_string($displayName) || $displayName === '') {
            return null;
        }

        $parts = explode('\\', $displayName);
        $shortName = (string) end($parts);

        return $shortName !== '' ? $shortName : $displayName;
    }

    private function extractRunEndpointForItemJobEndpointIdFromPayload(mixed $payload): ?int
    {
        if (! is_array($payload)) {
            return null;
        }

        $displayName = $payload['displayName'] ?? null;
        if ($displayName !== RunEndpointForItemJob::class) {
            return null;
        }

        $serializedCommand = $payload['data']['command'] ?? null;
        if (! is_string($serializedCommand) || $serializedCommand === '') {
            return null;
        }

        try {
            $job = unserialize($serializedCommand, ['allowed_classes' => [RunEndpointForItemJob::class]]);
            if (! $job instanceof RunEndpointForItemJob) {
                return null;
            }

            $reflection = new \ReflectionClass($job);
            if (! $reflection->hasProperty('endpointId')) {
                return null;
            }

            $property = $reflection->getProperty('endpointId');
            $property->setAccessible(true);
            $endpointId = $property->getValue($job);

            return is_int($endpointId) && $endpointId > 0 ? $endpointId : null;
        } catch (Throwable) {
            return null;
        }
    }
}
