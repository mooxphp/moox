<?php

declare(strict_types=1);

use Moox\MailTesting\Support\ByteFormat;
use Tests\TestCase;

uses(TestCase::class);

it('formats small and larger byte sizes', function (): void {
    expect(ByteFormat::bytes(16))->toBe('16 B')
        ->and(ByteFormat::bytes(10436))->toBe('10 KB');
});
