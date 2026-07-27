<?php

declare(strict_types=1);

use Moox\Transform\Support\TransformQueues;
use Tests\TestCase;

uses(TestCase::class);

test('transform queues resolve from transform config', function (): void {
    config()->set('transform.dispatch_queue', 'transform-dispatch');
    config()->set('transform.job_queue', 'transform-run');

    expect(TransformQueues::dispatch())->toBe('transform-dispatch')
        ->and(TransformQueues::run())->toBe('transform-run')
        ->and(TransformQueues::all())->toBe(['transform-dispatch', 'transform-run']);
});

test('transform queues deduplicate when dispatch and run share a queue', function (): void {
    config()->set('transform.dispatch_queue', 'transform');
    config()->set('transform.job_queue', 'transform');

    expect(TransformQueues::all())->toBe(['transform']);
});
