<?php

namespace Adrolli\FilamentJobManager\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

class QueueController extends Controller
{
    public function queueStatus()
    {
        $isRunning = Queue::isRunning('default');
        dd($isRunning);
    }

    public function startQueue()
    {
        // Start the queue worker
        Artisan::call('queue:work', ['--daemon' => true]);

        $this->queueStatus();
    }

    public function stopQueue()
    {
        // Stop the queue worker
        // Find the queue worker process ID
        //        $pidFilePath = storage_path('app/queue_worker.pid');
        $pidFilePath = base_path('storage/app/queue_worker.pid');

        $pid = file_get_contents($pidFilePath);

        if ($pid) {
            posix_kill($pid, SIGTERM);

            // Wait for the process to exit
            while (posix_kill($pid, 0)) {
                usleep(100000); // Sleep for 0.1 seconds
            }

            $this->info("Queue worker with PID $pid has stopped.");
        } else {
            $this->info('No queue worker process found.');
        }

        $this->queueStatus();
    }
}
