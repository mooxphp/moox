<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Moox\MailOutbox\Support\MailOutboxConfig;
use Moox\MailOutbox\Support\TestModeMessageTransformer;
use Moox\MailOutbox\Support\TestModeOutboundGuard;

class ApplyTestModeListener
{
    public function __construct(
        private MailOutboxConfig $config,
        private TestModeMessageTransformer $transformer,
    ) {
    }

    public function handle(MessageSending $event): void
    {
        if (! $this->config->isTestModeEnabled() || TestModeOutboundGuard::isHandling()) {
            return;
        }

        $this->transformer->apply($event->message, $this->config);
    }
}
