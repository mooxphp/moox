<?php

declare(strict_types=1);

use Moox\MailTesting\Support\DurationFormat;
use Tests\TestCase;

uses(TestCase::class);

it('keeps sub-second times in milliseconds', function (): void {
    expect(DurationFormat::milliseconds(0))->toBe('0 ms')
        ->and(DurationFormat::milliseconds(25))->toBe('25 ms')
        ->and(DurationFormat::milliseconds(374))->toBe('374 ms')
        ->and(DurationFormat::milliseconds(999))->toBe('999 ms');
});

it('switches to seconds and minutes for longer runs', function (): void {
    app()->setLocale('de');

    expect(DurationFormat::milliseconds(1000))->toBe('1 s')
        ->and(DurationFormat::milliseconds(2940))->toBe('2,94 s')
        ->and(DurationFormat::milliseconds(12_400))->toBe('12,4 s')
        ->and(DurationFormat::milliseconds(60_000))->toBe('1 min')
        ->and(DurationFormat::milliseconds(178_675))->toBe('2 min 59 s')
        ->and(DurationFormat::milliseconds(180_007))->toBe('3 min');
});
