<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Moox\MailTesting\Support\VariableStore;
use Tests\TestCase;

uses(TestCase::class);

it('returns defaults when nothing is stored', function (): void {
    Storage::fake((string) config('mail-testing.disk', 'local'));

    expect(app(VariableStore::class)->read())->toBe([
        'recipient_mode' => 'random',
        'variables' => [],
    ]);
});

it('round-trips recipient mode and bindings', function (): void {
    Storage::fake((string) config('mail-testing.disk', 'local'));

    $store = app(VariableStore::class);
    $store->write([
        'recipient_mode' => 'demo',
        'variables' => [
            ['token' => '{invoiceNumber}', 'mode' => 'random', 'value' => 'RE-####'],
        ],
    ]);

    expect($store->read())->toBe([
        'recipient_mode' => 'demo',
        'variables' => [
            ['token' => 'invoiceNumber', 'mode' => 'random', 'value' => 'RE-####'],
        ],
    ]);
});
