<?php

namespace Moox\Core\Console\Commands;

use Illuminate\Console\Command;
use Moox\Core\Jobs\PublishScheduledContentJob;

class PublishScheduledContentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch job to publish and unpublish scheduled content';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Dispatching scheduled content publishing job...');

        PublishScheduledContentJob::dispatch();

        $this->info('Job dispatched successfully!');

        return self::SUCCESS;
    }
}
