<?php

namespace Moox\Expiry\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

        // Example data (you can add any demo data)
        $demoData = [
            [
                'title' => 'Demo Document 1',
                'item_id' => 1,
                'expiry_job' => 'DemoJob',
                'category' => 'Documents',
                'status' => 'active',
                'expired_at' => Carbon::now()->addDays(30),
                'processing_deadline' => Carbon::now()->addDays(20),
                'notified_to' => 1,
                'escalated_to' => 2,
                'handled_by' => 3,
                'done_at' => Carbon::now()->addDays(5),
            ],
            [
                'title' => 'Demo Article 1',
                'item_id' => 2,
                'expiry_job' => 'DemoJob',
                'category' => 'Articles',
                'status' => 'inactive',
                'expired_at' => Carbon::now()->addDays(60),
                'processing_deadline' => Carbon::now()->addDays(30),
                'notified_to' => 2,
                'escalated_to' => 3,
                'handled_by' => 1,
                'done_at' => Carbon::now()->addDays(10),
            ],
            [
                'title' => 'Demo Task 1',
                'item_id' => 3,
                'expiry_job' => 'DemoJob',
                'category' => 'Tasks',
                'status' => 'active',
                'expired_at' => Carbon::now()->addDays(90),
                'processing_deadline' => Carbon::now()->addDays(50),
                'notified_to' => 3,
                'escalated_to' => 1,
                'handled_by' => 2,
                'done_at' => Carbon::now()->addDays(15),
            ],
        ];

        $cycleOptions = config('expiry.cycle_options');

        $this->setProgress(50);

        foreach ($demoData as $data) {
            Expiry::updateOrCreate(
                ['item_id' => $data['item_id']],
                [
                    'slug' => Str::slug($data['title']),
                    'link' => 'http://example.com/'.Str::slug($data['title']),
                    'title' => $data['title'],
                    'expiry_job' => $data['expiry_job'],
                    'category' => $data['category'],
                    'status' => $data['status'],
                    'expired_at' => $data['expired_at'],
                    'processing_deadline' => $data['processing_deadline'],
                    'notified_to' => $data['notified_to'],
                    'escalated_to' => $data['escalated_to'],
                    'handled_by' => $data['handled_by'],
                    'done_at' => $data['done_at'],
                    'cycle' => array_rand($cycleOptions), // Random cycle
                    'meta_id' => null,
                    'notified_at' => Carbon::now(),
                    'escalated_at' => Carbon::now()->addDays(2),
                ]
            );
        }

        $this->setProgress(100);
    }
}
