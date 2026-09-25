<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Moox\MailOutbox\Jobs\RecordSentMailJob;
use Moox\MailOutbox\Support\MailableInspector;
use Moox\MailOutbox\Support\MailOutboxConfig;

class RecordSentMailListener
{
    public function __construct(
        private MailableInspector $inspector,
        private MailOutboxConfig $config,
    ) {
    }

    public function handle(MessageSent $event): void
    {
        RecordSentMailJob::dispatch(
            $this->inspector->recordedSnapshotFromSent($event->sent, $event->data, $this->config),
        );
    }
}
