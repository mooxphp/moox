<?php

namespace Moox\Expiry\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Moox\Jobs\Traits\JobProgress;

class DemoExpiries implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, JobProgress, Queueable, SerializesModels;

    public $tries;

    public $timeout;

    public $maxExceptions;

    public $backoff;

    public function __construct()
    {
        $this->tries = 3;
        $this->timeout = 300;
        $this->maxExceptions = 1;
        $this->backoff = 350;
    }

    public function handle()
    {
        $this->setProgress(1);

        // Create demo licences
        $demoData = [
            ['licence_title' => 'Demo License 1', 'license_key' => 'DEMO123', 'expiry_date' => Carbon::now()->subDays(10)],
            ['licence_title' => 'Demo License 2', 'license_key' => 'DEMO456', 'expiry_date' => Carbon::now()->subDays(5)],
            ['licence_title' => 'Demo License 3', 'license_key' => 'DEMO789', 'expiry_date' => Carbon::now()->addDays(10)],
        ];

        foreach ($demoData as $data) {
            DB::table('licences')->updateOrInsert(['license_key' => $data['license_key']], $data);
        }

        $this->setProgress(100);
    }
}
