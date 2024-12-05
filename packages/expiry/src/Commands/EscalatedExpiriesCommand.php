<?php

namespace Moox\Expiry\Commands;

use Illuminate\Console\Command;
use Moox\Expiry\Jobs\SendEscalatedExpiriesJob;

class EscalatedExpiriesCommand extends Command
{
    protected $signature = 'mooxexpiry:escalated';

    protected $description = 'Dispatch the job to send notifications for escalated expiries';

    public function handle()
    {
        SendEscalatedExpiriesJob::dispatch();
        $this->info('Escalated Expiries Notification Dispatched');
    }
}
