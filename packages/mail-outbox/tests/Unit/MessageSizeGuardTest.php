<?php

declare(strict_types=1);

use Moox\MailOutbox\Exceptions\MessageTooLargeException;
use Moox\MailOutbox\Support\MessageSizeGuard;
use Moox\MailOutbox\Tests\Support\TestMailable;
use Moox\MailOutbox\Tests\TestCase;

uses(TestCase::class);

test('size guard allows messages under the ceiling', function (): void {
    $guard = new MessageSizeGuard;
    $mailable = new TestMailable(body: 'small');

    $guard->assertWithinLimit($mailable, 10_000);

    expect($guard->estimateBytes($mailable))->toBeLessThan(10_000);
});

test('size guard throws domain exception when over the ceiling', function (): void {
    $guard = new MessageSizeGuard;
    $mailable = new TestMailable(body: str_repeat('y', 500));

    $guard->assertWithinLimit($mailable, 10);
})->throws(MessageTooLargeException::class);

test('size guard counts path attachments toward the ceiling', function (): void {
    $path = sys_get_temp_dir().'/mail-outbox-size-'.uniqid('', true).'.bin';
    file_put_contents($path, str_repeat('z', 5_000));

    try {
        $guard = new MessageSizeGuard;
        $mailable = (new TestMailable(body: 'tiny'))->attach($path);

        $without = $guard->estimateBytes(new TestMailable(body: 'tiny'));
        $with = $guard->estimateBytes($mailable);

        expect($with)->toBeGreaterThanOrEqual($without + 5_000);

        $guard->assertWithinLimit($mailable, $without + 100);
    } finally {
        @unlink($path);
    }
})->throws(MessageTooLargeException::class);

test('size guard counts attachData payloads toward the ceiling', function (): void {
    $guard = new MessageSizeGuard;
    $payload = str_repeat('a', 4_000);
    $mailable = (new TestMailable(body: 'tiny'))->attachData($payload, 'blob.bin');

    expect($guard->estimateBytes($mailable))->toBeGreaterThanOrEqual(4_000);

    $guard->assertWithinLimit($mailable, 500);
})->throws(MessageTooLargeException::class);
