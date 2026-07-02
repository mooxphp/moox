<?php

declare(strict_types=1);

use Moox\Transform\Jobs\RunTransformRecordJob;
use Tests\TestCase;

uses(TestCase::class);

test('run transform record job has no timeout and uses transform queue', function (): void {
    config()->set('transform.job_timeout', 0);
    config()->set('transform.job_queue', 'transform');

    $job = new RunTransformRecordJob(1);

    expect($job->timeout)->toBe(0)
        ->and($job->queue)->toBe('transform');
});
