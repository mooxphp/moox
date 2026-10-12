<?php

declare(strict_types=1);

use Moox\Migrate\Support\MigrateOutput;

it('keeps short artisan output unchanged', function (): void {
    expect(MigrateOutput::forNotification("INFO  Running migrations.\n"))
        ->toBe('INFO  Running migrations.');
});

it('returns null for empty output', function (): void {
    expect(MigrateOutput::forNotification('   '))->toBeNull()
        ->and(MigrateOutput::forNotification(null))->toBeNull();
});

it('keeps the last lines and marks truncation', function (): void {
    $lines = [];

    for ($index = 1; $index <= 40; $index++) {
        $lines[] = 'line '.$index;
    }

    $result = MigrateOutput::forNotification(implode("\n", $lines));

    expect(explode("\n", (string) $result)[0])->toBe('line 11')
        ->and($result)->toContain('line 40')
        ->and($result)->toContain(MigrateOutput::TRUNCATED_MARKER);
});

it('strips ansi sequences before truncating', function (): void {
    expect(MigrateOutput::forNotification("\e[32mOK\e[0m"))->toBe('OK');
});
