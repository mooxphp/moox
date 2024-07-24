<?php

namespace Moox\Expiry\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Moox\Expiry\Models\Expiry;
use Moox\Jobs\Traits\JobProgress;

class CollectExpiries implements ShouldQueue
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

        // Collect expiries from the licences table with an expiry_date column
        $expiries = DB::table('licences')->where('expiry_date', '<', Carbon::now())->get();

        $this->setProgress(50);

        foreach ($expiries as $expiry) {
            $expiryData = [
                'item_id' => $expiry->id,
                'expired_at' => Carbon::parse($expiry->expiry_date),
                'title' => $expiry->licence_title,
                'slug' => Str::slug($expiry->licence_title),
                'link' => 'http://example.com/licence/'.$expiry->id,
                'notified_to' => 'admin@example.com',
                'expiry_job' => 'Collect Expiry Job',
            ];

            Expiry::updateOrCreate(['item_id' => $expiry->id], $expiryData);
        }

        $this->setProgress(100);
    }
}
