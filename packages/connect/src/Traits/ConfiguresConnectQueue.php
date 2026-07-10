<?php

declare(strict_types=1);

namespace Moox\Connect\Traits;

use Moox\Connect\Support\ConnectQueueSettingsResolver;

trait ConfiguresConnectQueue
{
    public int $tries;

    public int $timeout;

    protected function configureConnectQueue(string $jobType, ?int $endpointId = null, ?int $connectionId = null): void
    {
        app(ConnectQueueSettingsResolver::class)
            ->resolve($jobType, $endpointId, $connectionId)
            ->applyTo($this);
    }
}
