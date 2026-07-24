<?php

declare(strict_types=1);

namespace Moox\Connect\Traits;

use Moox\Connect\Support\ConnectQueueSettings;
use Moox\Connect\Support\ConnectQueueSettingsResolver;

trait ConfiguresConnectQueue
{
    public int $tries;

    public int $timeout;

    protected ConnectQueueSettings $connectQueueSettings;

    protected function configureConnectQueue(string $jobType, ?int $endpointId = null, ?int $connectionId = null): void
    {
        $this->connectQueueSettings = app(ConnectQueueSettingsResolver::class)
            ->resolve($jobType, $endpointId, $connectionId);

        $this->connectQueueSettings->applyTo($this);
    }
}
