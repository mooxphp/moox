<?php

declare(strict_types=1);

it('orders the runs table migration before messages because of the foreign key', function (): void {
    $names = collect(glob(dirname(__DIR__, 2).'/database/migrations/*.php'))
        ->map(fn (string $path): string => basename($path, '.php'))
        ->sort()
        ->values();

    $runs = $names->search(fn (string $name): bool => str_contains($name, 'mail_testing_runs'));
    $messages = $names->search(fn (string $name): bool => str_contains($name, 'mail_testing_messages'));

    expect($runs)->toBeInt()
        ->and($messages)->toBeInt()
        ->and($runs)->toBeLessThan($messages);
});
