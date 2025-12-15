<?php

namespace Moox\Prompts\Support;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
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
        $this->cache->forget($this->key($flowId));
    }

    protected function key(string $flowId): string
    {
        return $this->prefix.$flowId;
    }
}
